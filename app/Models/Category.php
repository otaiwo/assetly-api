<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'name',
        'description',
    ];

    // Optional: Customize table name if different
    // protected $table = 'categories';

    // Optional: Cast attributes
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    // Optional: Relationships (if you have products, posts, etc.)
    public function products()
    {
        return $this->hasMany(Product::class); // example
    }

    // Optional: Accessors/Mutators
    public function getNameAttribute($value)
    {
        return ucfirst($value); // auto-capitalize category names
    }
}
