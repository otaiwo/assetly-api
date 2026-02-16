<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // Require auth for create, update, delete
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    /**
     * List all products (with optional category filter)
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'user'])
            ->where('status', 'approved');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Show a single product
     */
    public function show(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => $product->load('category', 'user')
        ]);
    }

    /**
     * Create a new product
     */
    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|max:2048'
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'pending';

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('products', 'public');
        }

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product submitted successfully and is pending approval',
            'data' => $product->load('category', 'user')
        ], 201);
    }

    /**
     * Update a product
     */
    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        // Prevent editing approved products unless admin
        if ($product->status === 'approved' && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit approved products'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'type' => 'required|in:free,pro',
            'credit_cost' => 'required_if:type,pro|integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')
                ->store('products', 'public');
        }

        $product->update($validated);

        return response()->json([
            'success' => true,
            'data' => $product->load('category', 'user')
        ]);
    }

    /**
     * Delete a product
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete(); // soft delete if Product model uses SoftDeletes

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }
}
