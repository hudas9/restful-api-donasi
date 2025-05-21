<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;
use Illuminate\Support\Facades\Log;
use Exception;
use RuntimeException;

class MidtransService
{
    public function __construct()
    {
        $this->initializeMidtrans();
    }

    protected function initializeMidtrans()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$clientKey = config('services.midtrans.client_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized');
        Config::$is3ds = config('services.midtrans.is_3ds');

        if (empty(Config::$serverKey)) {
            throw new RuntimeException('Midtrans server key is not configured');
        }
    }

    public function createTransaction(array $params)
    {
        try {
            $this->initializeMidtrans();
            return Snap::createTransaction($params);
        } catch (Exception $e) {
            Log::error('Midtrans transaction error: ' . $e->getMessage());
            throw new Exception('Payment gateway error: ' . $e->getMessage());
        }
    }

    public function handleNotification()
    {
        $this->initializeMidtrans();

        try {
            $notification = new Notification();

            return [
                'transaction_status' => $notification->transaction_status,
                'order_id' => $notification->order_id,
                'payment_type' => $notification->payment_type,
                'fraud_status' => $notification->fraud_status,
                'data' => $notification
            ];
        } catch (Exception $e) {
            Log::error('Midtrans notification error: ' . $e->getMessage());
            throw new Exception('Payment notification error: ' . $e->getMessage());
        }
    }
}
