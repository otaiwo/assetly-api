<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;

class ProductPolicy
{
    // Admin and Seller can create products
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'seller']);
    }

    // Admin can update anything, Seller can update their own product
    public function update(User $user, Product $product): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('seller') && $product->user_id === $user->id) {
            return true;
        }

        return false;
    }

    // Admin can delete anything, Seller can delete their own product
    public function delete(User $user, Product $product): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('seller') && $product->user_id === $user->id) {
            return true;
        }

        return false;
    }

    // Anyone authenticated can view
    public function view(User $user, Product $product): bool
    {
        return true;
    }
}
