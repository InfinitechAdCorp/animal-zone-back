<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'method',
        'enabled',
        'qr_path',
    ];

        public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

}
