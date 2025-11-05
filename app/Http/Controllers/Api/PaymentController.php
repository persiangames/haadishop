<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function initiate(Request $request, $orderId)
    {
        $user = $request->user();

        $order = Order::where('user_id', $user->id)
            ->findOrFail($orderId);

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Order is not pending payment.',
            ], 422);
        }

        $provider = $request->get('provider', 'zarinpal');

        $payment = $this->paymentService->createPayment($order, $provider);

        try {
            if ($provider === 'zarinpal') {
                $result = $this->paymentService->initiateZarinpalPayment($payment);

                return response()->json([
                    'message' => 'Payment initiated successfully.',
                    'payment_id' => $payment->id,
                    'authority' => $result['authority'],
                    'payment_url' => $result['payment_url'],
                ], 200);
            } elseif ($provider === 'stripe') {
                $result = $this->paymentService->initiateStripePayment($payment);

                return response()->json([
                    'message' => 'Payment initiated successfully.',
                    'payment_id' => $payment->id,
                    'client_secret' => $result['client_secret'],
                    'payment_intent_id' => $result['payment_intent_id'],
                ], 200);
            }

            return response()->json([
                'message' => 'Unsupported payment provider.',
            ], 422);
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function zarinpalCallback(Request $request)
    {
        $authority = $request->get('Authority');
        $status = $request->get('Status');

        if ($status !== 'OK') {
            return redirect(config('app.frontend_url') . '/payment/failed?authority=' . $authority);
        }

        $transaction = PaymentTransaction::where('gateway_txn_id', $authority)
            ->where('status', 'init')
            ->first();

        if (!$transaction) {
            return redirect(config('app.frontend_url') . '/payment/failed?authority=' . $authority);
        }

        $payment = $transaction->payment;
        $result = $this->paymentService->verifyZarinpalPayment($authority, $payment->amount);

        if ($result['success']) {
            return redirect(config('app.frontend_url') . '/payment/success?order_id=' . $payment->order_id);
        }

        return redirect(config('app.frontend_url') . '/payment/failed?authority=' . $authority);
    }

    public function verify(Request $request, $paymentId)
    {
        $payment = Payment::findOrFail($paymentId);

        if ($payment->provider === 'zarinpal') {
            $transaction = $payment->transactions()->where('status', 'init')->first();

            if ($transaction) {
                $result = $this->paymentService->verifyZarinpalPayment(
                    $transaction->gateway_txn_id,
                    $payment->amount
                );

                return response()->json($result, 200);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment verification not available.',
        ], 422);
    }
}

