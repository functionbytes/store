<?php
// FILE: platform/packages/payment/src/Services/Abstracts/PaymentAbstract.php
// This is a core Botble file provided for reference.
// DO NOT add or modify this file inside your PayU plugin folder.

namespace Functionbytes\Wompi\Services\Abstracts;

use Botble\Payment\Services\Traits\PaymentErrorTrait;
use Botble\Support\Services\ProduceServiceInterface;
use Exception;
use Illuminate\Http\Request;

abstract class PaymentAbstract implements ProduceServiceInterface
{
    use PaymentErrorTrait;

    protected string $currency;

    protected array $paymentData = [];

    public function __construct()
    {
        $this->currency = config('plugins.payment.payment.currency');
    }

    public function execute(Request $request): mixed
    {
        // Debug log para confirmar que execute() se llama
        file_put_contents(storage_path('logs/wompi-execute.log'), "[" . date('Y-m-d H:i:s') . "] PaymentAbstract::execute() called\n", FILE_APPEND);
        
        try {
            $this->paymentData = $this->preparePaymentData($request);
            file_put_contents(storage_path('logs/wompi-execute.log'), "[" . date('Y-m-d H:i:s') . "] preparePaymentData completed\n", FILE_APPEND);

            $chargeId = $this->makePayment($request);
            file_put_contents(storage_path('logs/wompi-execute.log'), "[" . date('Y-m-d H:i:s') . "] makePayment completed, chargeId: " . ($chargeId ?: 'NULL') . "\n", FILE_APPEND);

            $this->afterMakePayment($request);
            file_put_contents(storage_path('logs/wompi-execute.log'), "[" . date('Y-m-d H:i:s') . "] afterMakePayment completed\n", FILE_APPEND);

            return $chargeId;
        } catch (Exception $exception) {
            file_put_contents(storage_path('logs/wompi-execute.log'), "[" . date('Y-m-d H:i:s') . "] Exception in execute(): " . $exception->getMessage() . "\n", FILE_APPEND);
            $this->setErrorMessageAndLogging($exception);

            return false;
        }
    }

    public function preparePaymentData(Request $request): array
    {
        $this->paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);

        return $this->paymentData;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getSupportRefundOnline(): bool
    {
        return $this->isSupportRefundOnline();
    }

    public function refundOrder(string $chargeId, float $amount, array $options = []): array
    {
        return $this->refund($chargeId, $amount, $options);
    }

    abstract public function makePayment(Request $request);

    abstract public function afterMakePayment(Request $request);

    abstract public function getServiceProvider(): string;

    abstract public function isSupportRefundOnline(): bool;

    abstract public function refund(string $chargeId, float $amount, array $options = []): array;
}
