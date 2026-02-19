<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'seller_id',
        'price_paid',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Parent order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Purchased product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Seller of the product
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getBuyerAttribute()
    {
        return $this->order->user;
    }

    public function isPaid()
    {
        return $this->order && $this->order->status === 'paid';
    }
}
