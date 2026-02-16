<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CreditTransaction;
use App\Models\UserCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;


class WebhookController extends Controller
{
    /**
     * ==============================
     * PAYSTACK WEBHOOK
     * ==============================
     */
    public function handlePaystack(Request $request)
    {
        $signature = $request->header('x-paystack-signature');

        $computedSignature = hash_hmac(
            'sha512',
            $request->getContent(),
            config('services.paystack.secret')
        );

        if ($signature !== $computedSignature) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $payload = $request->all();

        if (($payload['event'] ?? null) === 'charge.success') {

            $reference = $payload['data']['reference'] ?? null;
            $channel = $payload['data']['channel'] ?? null;

            // Try marking as ORDER first
            $this->markOrderPaid($reference, 'paystack', $channel);

            // Then try marking as CREDIT transaction
            $this->markTransactionCompleted($reference, 'paystack');
        }

        return response()->json(['message' => 'Webhook handled']);
    }

    /**
     * ==============================
     * MONIEPOINT WEBHOOK
     * ==============================
     */
    public function handleMoniepoint(Request $request)
    {
        // TODO: Implement proper Moniepoint signature verification

        $payload = $request->all();

        if (($payload['status'] ?? null) === 'SUCCESS') {

            $reference = $payload['reference'] ?? null;

            $this->markOrderPaid($reference, 'moniepoint');
            $this->markTransactionCompleted($reference, 'moniepoint');
        }

        return response()->json(['message' => 'Webhook handled']);
    }

    /**
     * ==============================
     * STRIPE WEBHOOK
     * ==============================
     */
    public function handleStripe(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('stripe-signature');

        try {
    $event = Webhook::constructEvent(
        $payload,
        $sigHeader,
        config('services.stripe.webhook_secret')
    );
} catch (\UnexpectedValueException $e) {
    return response()->json(['message' => 'Invalid payload'], 400);
} catch (SignatureVerificationException $e) {
    return response()->json(['message' => 'Invalid signature'], 400);
}


        if ($event->type === 'payment_intent.succeeded') {

            $paymentIntent = $event->data->object;

            $reference = $paymentIntent->metadata->internal_reference
                ?? $paymentIntent->metadata->reference
                ?? null;

            $method = $paymentIntent->payment_method_types[0] ?? null;

            $this->markOrderPaid($reference, 'stripe', $method);
            $this->markTransactionCompleted($reference, 'stripe');
        }

        return response()->json(['message' => 'Webhook handled']);
    }

    /**
     * ==============================
     * MARK ORDER AS PAID
     * ==============================
     */
    protected function markOrderPaid($internalReference, $gateway, $method = null)
    {
        if (! $internalReference) {
            return;
        }

        DB::transaction(function () use ($internalReference, $gateway, $method) {

            $order = Order::where('internal_reference', $internalReference)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $order) {
                return;
            }

            $order->update([
                'status' => 'paid',
                'payment_gateway' => $gateway,
                'gateway_reference' => $internalReference,
                'payment_method' => $method,
                'paid_at' => now(),
            ]);
        });
    }

    /**
     * ==============================
     * MARK CREDIT TRANSACTION COMPLETED
     * ==============================
     */
    protected function markTransactionCompleted($reference, $gateway)
    {
        if (! $reference) {
            return;
        }

        DB::transaction(function () use ($reference, $gateway) {

            $transaction = CreditTransaction::where('reference', $reference)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return;
            }

            $user = $transaction->user; // requires relationship

            if (! $user) {
                return;
            }

            // Add credits
            UserCredit::create([
                'user_id' => $user->id,
                'amount' => $transaction->amount,
                'type' => 'recharge',
                'expires_at' => null,
            ]);

            // Mark transaction completed
            $transaction->update([
                'status' => 'completed',
                'source' => $gateway,
            ]);
        });
    }
}
