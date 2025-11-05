<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function createPayment(Order $order, string $provider = 'zarinpal')
    {
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => $provider,
            'status' => 'init',
            'amount' => $order->due_total,
            'currency_code' => $order->currency_code,
        ]);

        return $payment;
    }

    public function initiateZarinpalPayment(Payment $payment)
    {
        $merchantId = config('services.zarinpal.merchant_id');
        $amount = $payment->amount; // به تومان
        $callbackUrl = config('app.url') . '/api/payments/zarinpal/callback';

        $response = Http::post('https://api.zarinpal.com/pg/v4/payment/request.json', [
            'merchant_id' => $merchantId,
            'amount' => $amount,
            'description' => 'Payment for Order #' . $payment->order->order_number,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'order_id' => $payment->order_id,
                'payment_id' => $payment->id,
            ],
        ]);

        $data = $response->json();

        if ($data['data']['code'] == 100) {
            $authority = $data['data']['authority'];

            // ثبت تراکنش
            PaymentTransaction::create([
                'payment_id' => $payment->id,
                'gateway_txn_id' => $authority,
                'raw_payload' => $data,
                'status' => 'init',
                'amount' => $amount,
            ]);

            return [
                'authority' => $authority,
                'payment_url' => 'https://www.zarinpal.com/pg/StartPay/' . $authority,
            ];
        }

        throw new \Exception('Failed to initiate payment: ' . ($data['errors']['message'] ?? 'Unknown error'));
    }

    public function verifyZarinpalPayment(string $authority, int $amount)
    {
        $merchantId = config('services.zarinpal.merchant_id');

        $response = Http::post('https://api.zarinpal.com/pg/v4/payment/verify.json', [
            'merchant_id' => $merchantId,
            'authority' => $authority,
            'amount' => $amount,
        ]);

        $data = $response->json();

        if ($data['data']['code'] == 100) {
            $transaction = PaymentTransaction::where('gateway_txn_id', $authority)->first();

            if ($transaction) {
                $payment = $transaction->payment;
                $payment->update(['status' => 'succeeded']);

                $transaction->update([
                    'status' => 'succeeded',
                    'raw_payload' => $data,
                ]);

                // به‌روزرسانی سفارش
                $order = $payment->order;
                $order->update([
                    'status' => 'paid',
                    'paid_total' => $payment->amount,
                    'due_total' => 0,
                ]);

                // ارسال اعلان پرداخت موفق
                if (class_exists(\App\Services\NotificationService::class)) {
                    app(\App\Services\NotificationService::class)->sendOrderNotification($order, 'paid');
                }

                return [
                    'success' => true,
                    'payment' => $payment,
                    'order' => $order,
                ];
            }
        }

        return [
            'success' => false,
            'message' => $data['errors']['message'] ?? 'Payment verification failed',
        ];
    }

    public function initiateStripePayment(Payment $payment)
    {
        $stripeKey = config('services.stripe.secret');
        $amount = $payment->amount * 100; // تبدیل به سنت

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $stripeKey,
        ])->post('https://api.stripe.com/v1/payment_intents', [
            'amount' => $amount,
            'currency' => strtolower($payment->currency_code),
            'description' => 'Payment for Order #' . $payment->order->order_number,
            'metadata' => [
                'order_id' => $payment->order_id,
                'payment_id' => $payment->id,
            ],
        ]);

        $data = $response->json();

        if (isset($data['id'])) {
            PaymentTransaction::create([
                'payment_id' => $payment->id,
                'gateway_txn_id' => $data['id'],
                'raw_payload' => $data,
                'status' => 'init',
                'amount' => $payment->amount,
            ]);

            return [
                'client_secret' => $data['client_secret'],
                'payment_intent_id' => $data['id'],
            ];
        }

        throw new \Exception('Failed to initiate Stripe payment');
    }
}

