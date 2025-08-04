<?php
// FILE: platform/packages/payment/src/Services/Traits/PaymentTrait.php
// This is a core Botble file provided for reference.
// DO NOT add or modify this file inside your PayU plugin folder.

namespace Functionbytes\Wompi\Traits;

use Botble\Payment\Models\Payment;
use Botble\Payment\Repositories\Interfaces\PaymentInterface;
use Botble\Payment\Enums\PaymentStatusEnum;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Throwable;

trait PaymentsTrait
{
    public function storeLocalPayment(array $data = []): string
    {
        // Don't apply the PAYMENT_FILTER_PAYMENT_DATA filter here to avoid conflicts with ecommerce address loading
        // The data is already properly formatted when passed from WompiController callback
        
        $orderIds = (array) Arr::get($data, 'order_id', []);

        $payment = app(PaymentInterface::class)->create([
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'payment_channel' => $data['payment_channel'],
            'charge_id' => $data['charge_id'],
            'order_id' => Arr::first($orderIds),
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
            'status' => $data['status'],
            'payment_type' => Arr::get($data, 'payment_type', 'direct'),
        ]);

        if (count($orderIds) > 1) {
            $payment->orders()->sync($orderIds);
        }

        return $payment->charge_id;
    }

    public function afterPaymentCompleted(string|null $chargeId): bool
    {
        $payment = app(PaymentInterface::class)->getFirstBy(['charge_id' => $chargeId]);

        if (! $payment) {
            return false;
        }

        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'charge_id' => $chargeId,
            'order_id' => $payment->order_id,
            'status' => PaymentStatusEnum::COMPLETED,
            'payment_channel' => $payment->payment_channel,
            'customer_id' => $payment->customer_id,
            'customer_type' => $payment->customer_type,
            'payment_type' => $payment->payment_type ?? 'direct',
        ]);

        return $this->processPayment($payment);
    }

    protected function processPayment(Model|Payment $payment): bool
    {
        try {
            DB::beginTransaction();

            $this->beforeProcessPayment($payment);

            $payment->status = $payment->status->getValue();

            do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                'charge_id' => $payment->charge_id,
                'order_id' => $payment->order_id,
            ]);

            DB::commit();

            return true;
        } catch (Throwable $exception) {
            DB::rollBack();
            report($exception);
        }

        return false;
    }

    protected function beforeProcessPayment(Model|Payment $payment): void
    {
        // to be implemented
    }

    /**
     * @deprecated
     */
    public function getPaymentDetails(string $chargeId): Model|Payment|null
    {
        return app(PaymentInterface::class)->getFirstBy(['charge_id' => $chargeId]);
    }

    /**
     * @deprecated since v5.15
     */
    public function success(Request $request, string $message = null): void
    {
        $this->afterPaymentCompleted($request->input('charge_id'));
    }

    /**
     * @deprecated since v5.15
     */
    public function error(Request $request, string $message = null): void
    {
        $this->afterPaymentCompleted($request->input('charge_id'));
    }

    public function getPaymentStatus(Request $request): Enum|string|null
    {
        $chargeId = $request->input('charge_id');

        if (! $chargeId) {
            return null;
        }

        $payment = $this->getPaymentDetails($chargeId);

        if (! $payment) {
            return null;
        }

        return $payment->status;
    }

    /**
     * Create initial pending payment record when starting payment process
     */
    public function createPendingPayment(array $data = []): string
    {
        // Don't apply the PAYMENT_FILTER_PAYMENT_DATA filter here to avoid conflicts with ecommerce address loading
        // The data is already properly formatted when passed from WompiPaymentService
        
        $orderIds = (array) Arr::get($data, 'order_id', []);

        // Generate a temporary charge_id for the pending payment
        $chargeId = 'WP' . time() . '_' . Str::random(8);

        $payment = app(PaymentInterface::class)->create([
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'payment_channel' => $data['payment_channel'],
            'charge_id' => $chargeId,
            'order_id' => Arr::first($orderIds),
            'customer_id' => Arr::get($data, 'customer_id'),
            'customer_type' => Arr::get($data, 'customer_type'),
            'status' => PaymentStatusEnum::PENDING,
            'payment_type' => Arr::get($data, 'payment_type', 'direct'),
        ]);

        if (count($orderIds) > 1) {
            $payment->orders()->sync($orderIds);
        }

        return $chargeId;
    }

    /**
     * Update an existing payment with new charge_id (when Wompi transaction ID is received)
     */
    public function updatePaymentChargeId(string $oldChargeId, string $newChargeId, array $metadata = []): bool
    {
        $payment = app(PaymentInterface::class)->getFirstBy(['charge_id' => $oldChargeId]);

        if (!$payment) {
            return false;
        }

        $payment->charge_id = $newChargeId;
        if (!empty($metadata)) {
            $payment->metadata = array_merge($payment->metadata ?? [], $metadata);
        }
        $payment->save();

        return true;
    }
}
