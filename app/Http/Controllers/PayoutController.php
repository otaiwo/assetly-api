<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PayoutController extends Controller
{
    public function index()
    {
        $payouts = Payout::with('user')->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payouts
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->balance < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have enough balance for this payout'
            ], 403);
        }

        DB::transaction(function () use ($user, $request) {
            $payout = Payout::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'status' => 'pending',
                'method' => $request->method,
                'reference' => Str::uuid(),
            ]);

            // deduct balance immediately to avoid double payout
            $user->decrement('balance', $request->amount);
        });

        return response()->json([
            'success' => true,
            'message' => 'Payout created successfully'
        ]);
    }

    public function markAsPaid(Payout $payout)
    {
        if ($payout->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Payout is already processed'
            ], 400);
        }

        $payout->update([
            'status' => 'completed',
            'processed_at' => now()
        ]);

        // Here you could trigger Stripe/PayPal payout API

        return response()->json([
            'success' => true,
            'message' => 'Payout marked as completed',
            'data' => $payout
        ]);
    }
}
