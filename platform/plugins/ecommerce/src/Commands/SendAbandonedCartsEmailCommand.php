<?php

namespace Botble\Ecommerce\Commands;

use Botble\Base\Facades\EmailHandler;
use Botble\Ecommerce\Facades\OrderHelper;
use Botble\Ecommerce\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand('cms:abandoned-carts:email', 'Send emails for abandoned carts')]
class SendAbandonedCartsEmailCommand extends Command
{
    protected $signature = 'cms:abandoned-carts:email 
                           {--hours=1 : Minimum hours since cart was abandoned} 
                           {--max-hours=168 : Maximum hours since cart was abandoned (default 7 days)}
                           {--limit=50 : Maximum number of emails to send per run}
                           {--template=abandoned_cart : Email template to use}
                           {--dry-run : Preview what would be sent without actually sending}';

    protected $description = 'Send abandoned cart recovery emails to customers';

    public function handle(): int
    {
        $minHours = (int) $this->option('hours');
        $maxHours = (int) $this->option('max-hours');
        $limit = (int) $this->option('limit');
        $template = $this->option('template');
        $dryRun = $this->option('dry-run');

        // Calculate time boundaries
        $minTime = Carbon::now()->subHours($maxHours);
        $maxTime = Carbon::now()->subHours($minHours);

        $this->info("🔍 Searching for abandoned carts...");
        $this->info("   • Created between: {$minTime->format('Y-m-d H:i')} and {$maxTime->format('Y-m-d H:i')}");
        $this->info("   • Limit: {$limit} emails");
        $this->info("   • Template: {$template}");

        // Find abandoned orders (incomplete orders with email addresses)
        $query = Order::query()
            ->with(['user', 'address', 'products'])
            ->where('is_finished', 0)
            ->whereBetween('created_at', [$minTime, $maxTime])
            ->whereHas('products') // Only orders with products
            ->where(function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->whereNotNull('email');
                })->orWhereHas('address', function ($q) {
                    $q->whereNotNull('email');
                });
            });

        // Exclude orders that already received abandoned cart emails recently
        $query->whereDoesntHave('histories', function ($q) use ($minTime) {
            $q->where('action', 'abandoned_cart_email_sent')
              ->where('created_at', '>=', $minTime);
        });

        $orders = $query->limit($limit)->get();

        if ($orders->isEmpty()) {
            $this->info("✅ No abandoned carts found matching the criteria.");
            return self::SUCCESS;
        }

        $this->info("📧 Found {$orders->count()} abandoned cart(s)");

        if ($dryRun) {
            $this->warn("🔍 DRY RUN MODE - No emails will be sent");
            $this->table(
                ['Order ID', 'Customer', 'Email', 'Items', 'Amount', 'Created'],
                $orders->map(function ($order) {
                    return [
                        $order->code,
                        $order->user->name ?: $order->address->name,
                        $order->user->email ?: $order->address->email,
                        $order->products->count(),
                        format_price($order->amount),
                        $order->created_at->format('Y-m-d H:i'),
                    ];
                })
            );
            return self::SUCCESS;
        }

        $count = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($orders->count());
        $progressBar->start();

        foreach ($orders as $order) {
            $email = $order->user->email ?: $order->address->email;

            if (!$email) {
                $progressBar->advance();
                continue;
            }

            try {
                $mailer = EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME);
                
                // Check if template exists
                if (!$mailer->templateEnabled($template)) {
                    $this->error("\n❌ Email template '{$template}' is not enabled or doesn't exist");
                    return self::FAILURE;
                }

                $order->dont_show_order_info_in_product_list = true;

                // Set email variables
                $mailer = OrderHelper::setEmailVariables($order, $mailer);

                // Send the email
                $mailer->sendUsingTemplate($template, $email);

                // Log that we sent an abandoned cart email
                DB::table('ec_order_histories')->insert([
                    'action' => 'abandoned_cart_email_sent',
                    'description' => "Abandoned cart email sent to {$email}",
                    'order_id' => $order->id,
                    'user_id' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $count++;
            } catch (Throwable $exception) {
                $errors++;
                $this->error("\n❌ Failed to send email for order {$order->code}: " . $exception->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        if ($count > 0) {
            $this->info("✅ Successfully sent {$count} abandoned cart email" . ($count != 1 ? 's' : '') . "!");
        }

        if ($errors > 0) {
            $this->warn("⚠️  {$errors} email(s) failed to send");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
