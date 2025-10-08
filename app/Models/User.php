<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
        protected $fillable = [
            'name',
            'slug',
            'email',
            'contact_number',
            'password',
            'role',
            'region',
            'province',
            'city',
            'barangay',
            'street_address',
            'postal_code', // ✅ add this
        ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be type cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relationship: a user may have a seller verification
     */
    public function sellerVerification()
    {
        return $this->hasOne(SellerVerification::class, 'seller_id');
    }

    /**
     * Relationship: a seller has many products
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function deliveryInfo()
    {
        return $this->hasOne(DeliveryInfo::class);
    }

}
