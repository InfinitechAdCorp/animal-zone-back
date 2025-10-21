<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'image_path', 'is_primary'];

    // ✅ Automatically add a full image URL in API responses
    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        if ($this->image_path) {
            // If stored in public/uploads/products/
            if (file_exists(public_path($this->image_path))) {
                return url($this->image_path);
            }

            // If stored in storage/app/public/uploads/products/
            if (Storage::disk('public')->exists($this->image_path)) {
                return Storage::url($this->image_path);
            }
        }

        return null;
    }
}
