<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerVerification extends Model
{
    use HasFactory;

    // ✅ Table name (optional since Laravel auto-detects plural form)
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
}
