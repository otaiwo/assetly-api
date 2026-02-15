<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ProductStatus;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'user_id',
        'image',
        'status',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'status' => ProductStatus::class,
    ];

    protected $attributes = [
        'status' => ProductStatus::PENDING,
    ];

    // Relationships
    public function category() { return $this->belongsTo(Category::class); }
    public function user() { return $this->belongsTo(User::class); }

    // Accessors
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    // Status checks
    public function isApproved(): bool { return $this->status === ProductStatus::APPROVED; }
    public function isPending(): bool { return $this->status === ProductStatus::PENDING; }
    public function isRejected(): bool { return $this->status === ProductStatus::REJECTED; }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', ProductStatus::APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', ProductStatus::PENDING);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', ProductStatus::REJECTED);
    }
}
