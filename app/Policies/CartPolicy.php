<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;

class CartPolicy
{
    /**
     * Determine if the user can view the cart.
     * Users can view their own cart. Guests have no user_id, so handled in controller.
     */
    public function view(User $user, Cart $cart): bool
    {
        return $cart->user_id === $user->id || $user->role === 'admin';
    }

    /**
     * Determine if the user can add items to the cart.
     */
    public function add(User $user, Cart $cart): bool
    {
        return $cart->user_id === $user->id || $user->role === 'admin';
    }

    /**
     * Determine if the user can remove items from the cart.
     */
    public function remove(User $user, Cart $cart): bool
    {
        return $cart->user_id === $user->id || $user->role === 'admin';
    }

    /**
     * Admins can manage any cart.
     */
    public function manage(User $user, Cart $cart): bool
    {
        return $user->role === 'admin';
    }
}
