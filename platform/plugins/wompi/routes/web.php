<?php

use Functionbytes\Wompi\Http\Controllers\WompiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'core'])
    ->prefix('payment/wompi')
    ->name('payment.wompi.')
    ->group(function () {

        // Callback URL - donde Wompi redirige al usuario después del pago
        Route::get('callback', [WompiController::class, 'callback'])
            ->name('callback');

        // Webhook URL - para notificaciones servidor-a-servidor de Wompi
        Route::post('webhook', [WompiController::class, 'webhook'])
            ->name('webhook');

        // Endpoint opcional para consultar el estado de una transacción
        Route::get('transaction/{transactionId}', [WompiController::class, 'checkTransaction'])
            ->name('transaction.check');

        // Endpoint para simular webhook en desarrollo local
        if (app()->environment(['local', 'testing']) || config('app.debug')) {
            Route::get('simulate-webhook/{transactionId}', [WompiController::class, 'simulateWebhook'])
                ->name('simulate.webhook');

            // Debug endpoint para verificar configuración
            Route::get('debug-config', [WompiController::class, 'debugConfig'])
                ->name('debug.config');
        }
    });
