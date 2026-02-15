<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'amount',
        'currency',
        'status',
        'internal_reference',
        'payment_reference',
        'payment_gateway',
        'gateway_reference',
        'payment_method',
        'paid_at',
        'failed_at',
        'cancelled_at',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate UUIDs
     */
    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->internal_reference)) {
                $order->internal_reference = (string) Str::uuid();
            }

            if (empty($order->payment_reference)) {
                $order->payment_reference = (string) Str::uuid();
            }
        });
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
