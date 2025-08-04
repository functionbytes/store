<?php

namespace Botble\Ecommerce\Jobs;

use Botble\Base\Facades\EmailHandler;
use Botble\Ecommerce\Facades\OrderHelper;
use Botble\Ecommerce\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAbandonedCartEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected Order $order;
    protected string $template;

    public function __construct(Order $order, string $template = 'abandoned_cart')
    {
        $this->order = $order;
        $this->template = $template;
    }

    public function handle(): void
    {
        $email = $this->order->user->email ?: $this->order->address->email;

        if (!$email) {
            Log::warning("No email found for abandoned cart order: {$this->order->code}");
            return;
        }

        try {
            $mailer = EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME);

            // Check if template exists
            if (!$mailer->templateEnabled($this->template)) {
                Log::error("Email template '{$this->template}' is not enabled or doesn't exist");
                return;
            }

            $this->order->dont_show_order_info_in_product_list = true;

            // Set email variables
            $mailer = OrderHelper::setEmailVariables($this->order, $mailer);

            // Send the email
            $mailer->sendUsingTemplate($this->template, $email);

            // Log that we sent an abandoned cart email
            DB::table('ec_order_histories')->insert([
                'action' => 'abandoned_cart_email_sent',
                'description' => "Abandoned cart email sent to {$email} using template '{$this->template}'",
                'order_id' => $this->order->id,
                'user_id' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("Abandoned cart email sent successfully for order: {$this->order->code}");

        } catch (Throwable $exception) {
            Log::error("Failed to send abandoned cart email for order {$this->order->code}: " . $exception->getMessage());
            throw $exception; // Re-throw to trigger retry mechanism
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error("Abandoned cart email job failed permanently for order {$this->order->code}: " . $exception->getMessage());

        // Log the failure in order history
        DB::table('ec_order_histories')->insert([
            'action' => 'abandoned_cart_email_failed',
            'description' => "Failed to send abandoned cart email: " . $exception->getMessage(),
            'order_id' => $this->order->id,
            'user_id' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}