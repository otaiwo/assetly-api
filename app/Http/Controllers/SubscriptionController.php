<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\UserCredit;
use App\Models\CreditTransaction;
use App\Models\User;

class SubscriptionController extends Controller
{
    /**
     * Claim daily free credits (expires in 24h)
     */
 public function claimDailyCredits(Request $request)
{
    $user = Auth::user();

    // Check if user already claimed today using calendar day
    $alreadyClaimed = UserCredit::where('user_id', $user->id)
        ->where('type', 'daily_bonus')
        ->whereDate('created_at', now()->toDateString()) // only today
        ->exists();

    if ($alreadyClaimed) {
        return response()->json([
            'success' => false,
            'message' => 'You have already claimed your daily credits today.'
        ], 403);
    }

    $credits = 100; // daily bonus amount

    $creditBatch = UserCredit::create([
        'user_id' => $user->id,
        'amount' => $credits,
        'type' => 'daily_bonus',
        'expires_at' => now()->addDay(),
    ]);

    // Log transaction
    CreditTransaction::create([
        'user_id' => $user->id,
        'type' => 'credit',
        'amount' => $credits,
        'source' => 'daily_bonus',
        'reference' => Str::uuid(),
              'status' => 'completed', 
    ]);

    return response()->json([
        'success' => true,
        'message' => "You received {$credits} daily credits! They will expire in 24 hours.",
        'data' => $creditBatch
    ]);
}

    /**
     * Recharge credits via payment provider
     */
    public function recharge(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'provider' => 'required|in:stripe,paystack,monnify'
        ]);

        $user = Auth::user();
        $amount = $request->amount;
        $provider = $request->provider;

        // Generate unique reference for payment
        $reference = Str::uuid();

        // Store pending transaction
        CreditTransaction::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => $amount,
            'source' => $provider,
            'reference' => $reference,
        ]);

        // Here you’d integrate actual payment SDK (Stripe / Paystack / Monnify)
        // Example: return checkout URL / session info
        return response()->json([
            'success' => true,
            'message' => 'Payment initiated, complete the payment to receive credits.',
            'payment_reference' => $reference,
            'payment_url' => 'https://provider-checkout-url.example.com' // replace with actual SDK
        ]);
    }



    /**
     * Show user credit balance and history
     */
    public function balanceAndHistory()
    {
        $user = Auth::user();

        $balance = UserCredit::where('user_id', $user->id)
            ->where('used', false)
            ->where(function($q){
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->sum('amount');

        $transactions = CreditTransaction::where('user_id', $user->id)
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'balance' => $balance,
            'transactions' => $transactions
        ]);
    }

    /**
     * Optional: subscribe to unlimited pro plan
     */
    public function subscribePlan(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:pro_monthly,pro_yearly'
        ]);

        $user = Auth::user();
        $plan = $request->plan;
        $price = $plan === 'pro_monthly' ? 10 : 100; // USD example

        $reference = Str::uuid();

        // Store pending transaction
        CreditTransaction::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => 0, // optional, plan doesn’t give credits
            'source' => 'subscription_'.$plan,
            'reference' => $reference
        ]);

        // Integrate with Stripe/Paystack/Monnify for recurring payment
        return response()->json([
            'success' => true,
            'message' => "Subscription to {$plan} initiated.",
            'payment_reference' => $reference,
            'payment_url' => 'https://provider-subscription-checkout-url.example.com'
        ]);
    }
}
