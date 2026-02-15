<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    // Fetch all categories with dynamic pagination
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $categories = Category::paginate($perPage);

        // Wrap the collection in the resource
        return $this->successResponse(CategoryResource::collection($categories));
    }

    // Fetch a single category by ID
    public function show($id): JsonResponse
    {
        $category = Category::findOrFail($id);
        return $this->successResponse(new CategoryResource($category));
    }

    // Store, update, destroy – wrap in resource as well
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string'
        ]);

        $category = Category::create($request->only('name', 'description'));

        return $this->successResponse(new CategoryResource($category), 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $this->authorize('update', $category);

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string'
        ]);

        $category->update($request->only('name', 'description'));

        return $this->successResponse(new CategoryResource($category));
    }

    public function destroy($id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $this->authorize('delete', $category);

        $category->delete();

        return $this->successResponse(null, 200, 'Category deleted successfully');
    }
}
