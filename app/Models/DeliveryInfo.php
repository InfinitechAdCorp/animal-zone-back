<?php

// app/Models/DeliveryInfo.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryInfo extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
