<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ProductStatus;
use Illuminate\Support\Facades\Storage;

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
        'type',       // 'free' or 'pro'
        'credit_cost',  // credit cost for logged-in users
        'file_path',  // path to downloadable file
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
    public function downloads() { return $this->hasMany(Download::class); }

    // Accessors
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    // Status checks
    public function isApproved(): bool { return $this->status === ProductStatus::APPROVED; }
    public function isPending(): bool { return $this->status === ProductStatus::PENDING; }
    public function isRejected(): bool { return $this->status === ProductStatus::REJECTED; }

    // Type helpers
    public function isFree(): bool { return $this->type === 'free'; }
    public function isPro(): bool { return $this->type === 'pro'; }

    // File helper
    public function fileExists(): bool
    {
        return $this->file_path && Storage::disk('public')->exists($this->file_path);
    }

    // Scopes
    public function scopeApproved($query) { return $query->where('status', ProductStatus::APPROVED); }
    public function scopePending($query) { return $query->where('status', ProductStatus::PENDING); }
    public function scopeRejected($query) { return $query->where('status', ProductStatus::REJECTED); }
}
