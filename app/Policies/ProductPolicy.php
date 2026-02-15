<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Category;

class CategoryPolicy
{
    // Only admin can create a category
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    // Only admin can update a category
    public function update(User $user, Category $category): bool
    {
        return $user->is_admin;
    }

    // Only admin can delete a category
    public function delete(User $user, Category $category): bool
    {
        return $user->is_admin;
    }

    // You can allow everyone to view categories
    public function view(User $user, Category $category): bool
    {
        return true;
    }
}
