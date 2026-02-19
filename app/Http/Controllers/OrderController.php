<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\OrderResource;
use App\Http\Resources\CartItemResource;
use App\Traits\ApiResponse;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        // Protect checkout and order history
        $this->middleware('auth:sanctum')->only(['checkout', 'orders', 'show']);
    }

    /**
     * List logged-in user orders
     */
    public function orders(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('items.product')
            ->latest()
            ->paginate(10);

        return $this->success(OrderResource::collection($orders), 'Orders retrieved successfully.');
    }

    /**
     * Show single order
     */
    public function show($id)
    {
        $order = Order::with('items.product')->find($id);

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        $this->authorize('view', $order);

        return $this->success(new OrderResource($order), 'Order retrieved successfully.');
    }

    /**
     * View cart for logged-in user or guest
     */
    public function cart(Request $request)
    {
        $guestToken = $request->header('X-Guest-Cart');

        if ($request->user()) {
            $cart = Cart::firstOrCreate(
                ['user_id' => $request->user()->id]
            );
        } elseif ($guestToken) {
            $cart = Cart::firstOrCreate(
                ['guest_token' => $guestToken]
            );
        } else {
            return $this->success([], 'Your cart is empty.');
        }

        $items = $cart->items()->with('product')->get();

        return $this->success(CartItemResource::collection($items), 'Cart retrieved successfully.');
    }

    /**
     * Add product to cart (guest or logged-in)
     */
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::where('id', $validated['product_id'])
            ->where('status', 'approved')
            ->first();

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        // Determine cart
        if ($request->user()) {
            $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        } else {
            $guestToken = $request->header('X-Guest-Cart') ?? Str::uuid()->toString();
            $cart = Cart::firstOrCreate(['guest_token' => $guestToken]);
        }

        // Prevent duplicate product in cart
        $cartItem = $cart->items()->firstOrCreate(
            ['product_id' => $product->id],
            ['price_snapshot' => $product->price]
        );

        return $this->success([
            'message' => 'Item added to cart',
            'cart_item' => new CartItemResource($cartItem),
            'guest_cart_token' => $guestToken ?? null,
        ]);
    }

    /**
     * Checkout: convert cart → order
     */
    public function checkout(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->error('Authentication required', 401);
        }

        $cart = Cart::with('items.product')->where('user_id', $user->id)->first();
        if (!$cart || $cart->items->isEmpty()) {
            return $this->error('Cart is empty', 400);
        }

        DB::beginTransaction();

        try {
            // Calculate totals
            $total = $cart->items->sum(fn($item) => $item->price_snapshot);

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $total,
                'currency' => 'NGN',
                'internal_reference' => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
                'payment_reference' => 'PAY-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8)),
                'status' => 'pending',
            ]);

            // Move items to order_items
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'seller_id' => $item->product->user_id, // assuming product belongs to a seller
                    'price_paid' => $item->price_snapshot,
                ]);
            }

            // Clear cart
            $cart->items()->delete();

            DB::commit();

            return $this->success(new OrderResource($order), 'Order created successfully.', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Checkout failed: ' . $e->getMessage(), 500);
        }
    }
}
