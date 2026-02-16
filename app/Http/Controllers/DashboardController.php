<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\UserCredit;

class DashboardController extends Controller
{
    // Require auth
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Show basic dashboard stats for the logged-in user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Example stats
        $totalProducts = $user->products()->count();
        $totalOrders = $user->orders()->count();
        $creditBalance = UserCredit::where('user_id', $user->id)
            ->where('used', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'total_products' => $totalProducts,
                'total_orders' => $totalOrders,
                'credit_balance' => $creditBalance,
            ]
        ]);
    }
}
