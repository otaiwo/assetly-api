<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any orders.
     * Admins can view all.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view a specific order.
     * Users can view their own orders.
     * Admins can view any.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->role === 'admin'
            || $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can create orders.
     * Any authenticated user can create.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update an order.
     * Only admin can update.
     * And never allow updates if already paid.
     */
    public function update(User $user, Order $order): bool
    {
        if ($order->status === 'paid') {
            return false;
        }

        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update order status.
     * Strictly admin only.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete an order.
     * Only admin, and not if paid.
     */
    public function delete(User $user, Order $order): bool
    {
        if ($order->status === 'paid') {
            return false;
        }

        return $user->role === 'admin';
    }

    /**
     * Restore (if using soft deletes)
     */
    public function restore(User $user, Order $order): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Force delete permanently
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return $user->role === 'admin';
    }
}
