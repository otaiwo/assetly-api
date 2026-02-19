<?php

namespace App\Policies;

use App\Models\OrderItem;
use App\Models\User;

class OrderItemPolicy
{
    /**
     * Determine if the user can view a specific order item.
     */
    public function view(User $user, OrderItem $item): bool
    {
        return $user->role === 'admin' || $item->order->user_id === $user->id;
    }

    /**
     * Determine if the user can update an order item.
     * Only admin can update, and only if the parent order is not paid.
     */
    public function update(User $user, OrderItem $item): bool
    {
        if ($item->order->status === 'paid') return false;
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can delete an order item.
     */
    public function delete(User $user, OrderItem $item): bool
    {
        if ($item->order->status === 'paid') return false;
        return $user->role === 'admin';
    }

    /**
     * Restore or force delete
     */
    public function restore(User $user, OrderItem $item): bool
    {
        return $user->role === 'admin';
    }

    public function forceDelete(User $user, OrderItem $item): bool
    {
        return $user->role === 'admin';
    }
}
