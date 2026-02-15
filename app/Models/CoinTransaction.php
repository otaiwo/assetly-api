<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'reference',
        'amount',
        'type',
        'source',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
