<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Download extends Model
{
    use SoftDeletes;

    protected $table = 'downloads';

    // Mass assignable fields
    protected $fillable = [
        'user_id',
        'guest_id',
        'product_id',
        'ip_address',
        'device_id',
    ];

    // Casts
    protected $casts = [
        'user_id' => 'integer',
        'product_id' => 'integer',
        'guest_id' => 'string',
        'ip_address' => 'string',
        'device_id' => 'string',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
