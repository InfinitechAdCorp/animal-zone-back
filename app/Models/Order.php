<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_method',
        'subtotal',
        'shipping',
        'total',
        'delivery_info',
        'status',
    ];

    protected $casts = [
        'delivery_info' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
{
    return $this->hasMany(OrderItem::class);
}

}
