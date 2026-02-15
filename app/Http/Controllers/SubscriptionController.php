<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\UserCoin;
use App\Models\CoinTransaction;
use App\Models\User;

class SubscriptionController extends Controller
{
    /**
     * Claim daily free coins (expires in 24h)
     */
 public function claimDailyCoins(Request $request)
{
    $user = Auth::user();

    // Check if user already claimed today using calendar day
    $alreadyClaimed = UserCoin::where('user_id', $user->id)
        ->where('type', 'daily_bonus')
        ->whereDate('created_at', now()->toDateString()) // only today
        ->exists();

    if ($alreadyClaimed) {
        return response()->json([
            'success' => false,
            'message' => 'You have already claimed your daily coins today.'
        ], 403);
    }

    $coins = 100; // daily bonus amount

    $coinBatch = UserCoin::create([
        'user_id' => $user->id,
        'amount' => $coins,
        'type' => 'daily_bonus',
        'expires_at' => now()->addDay(),
    ]);

    // Log transaction
    CoinTransaction::create([
        'user_id' => $user->id,
        'type' => 'credit',
        'amount' => $coins,
        'source' => 'daily_bonus',
        'reference' => Str::uuid(),
              'status' => 'completed', 
    ]);

    return response()->json([
        'success' => true,
        'message' => "You received {$coins} daily coins! They will expire in 24 hours.",
        'data' => $coinBatch
    ]);
}

    /**
     * Recharge coins via payment provider
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
        CoinTransaction::create([
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
            'message' => 'Payment initiated, complete the payment to receive coins.',
            'payment_reference' => $reference,
            'payment_url' => 'https://provider-checkout-url.example.com' // replace with actual SDK
        ]);
    }



    /**
     * Show user coin balance and history
     */
    public function balanceAndHistory()
    {
        $user = Auth::user();

        $balance = UserCoin::where('user_id', $user->id)
            ->where('used', false)
            ->where(function($q){
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->sum('amount');

        $transactions = CoinTransaction::where('user_id', $user->id)
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
        CoinTransaction::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => 0, // optional, plan doesn’t give coins
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
