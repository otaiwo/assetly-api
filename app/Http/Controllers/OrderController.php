<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List authenticated user's orders
     */
    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('product')
            ->latest()
            ->paginate(10);

        return $this->success($orders, 'Orders retrieved successfully.');
    }

    /**
     * Create a new order (one-time purchase)
     */
public function store(Request $request)
{
    $validated = $request->validate([
        'product_id' => ['required', 'exists:products,id'],
    ]);

    $user = $request->user();

    $product = Product::where('id', $validated['product_id'])
        ->where('status', 'approved')
        ->firstOrFail();

    // Check for existing pending order before creating
    $alreadyPending = Order::where('user_id', $user->id)
        ->where('product_id', $product->id)
        ->where('status', 'pending')
        ->exists();

    if ($alreadyPending) {
        return $this->error('You already have a pending order for this product.', 400);
    }

    // Create order safely inside a transaction
    $order = DB::transaction(function () use ($user, $product) {
        return Order::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => $product->price,
            'currency' => 'NGN',
            'status' => 'pending',
        ]);
    });

    return $this->success($order, 'Order created successfully.', 201);
}

    /**
     * Show single order
     */
    public function show(Order $order)
    {
        $this->authorize('view', $order);

        return $this->success(
            $order->load('product'),
            'Order retrieved successfully.'
        );
    }
}
