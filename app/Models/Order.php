<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'currency',
        'internal_reference',
        'payment_reference',
        'payment_gateway',
        'payment_method',
        'platform_fee',
        'seller_earnings',
        'status',
        'paid_at',
        'failed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'seller_earnings' => 'decimal:2',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($order) {

            if (empty($order->internal_reference)) {
                $order->internal_reference =
                    'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
            }

            // Only auto-generate if you want internal fallback
            if (empty($order->payment_reference)) {
                $order->payment_reference =
                    'PAY-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Scopes
    |--------------------------------------------------------------------------
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

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed()
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
        ]);
    }
}
