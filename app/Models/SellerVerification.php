<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SellerVerification extends Model
{
    use HasFactory;

    // ✅ Table name (optional, but fine to include)
    protected $table = 'seller_verifications';

    // ✅ Mass assignable fields
    protected $fillable = [
        'seller_id',
        'company_name',
        'gov_id_type',
        'gov_id',
        'selfie_with_id',
        'proof_of_address',
        'business_permit_types',
        'dti_sec',
        'mayors_permit',
        'bir_certificate',
        'fda_certificate',
        'product_labels',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'remarks',
    ];

    protected $casts = [
        'business_permit_types' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    // ✅ Relationships
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ✅ Automatically update user's slug based on company name
    protected static function booted()
    {
        static::saved(function ($verification) {
            $user = $verification->seller;

            if ($user && $user->role === 'seller' && $verification->company_name) {
                $baseSlug = Str::slug($verification->company_name);
                $slug = $baseSlug;
                $counter = 1;

                // Ensure slug is unique
                while (\App\Models\User::where('slug', $slug)
                    ->where('id', '!=', $user->id)
                    ->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }

                // Update user's slug
                $user->update(['slug' => $slug]);
            }
        });
    }
}
