<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'seller_id',
        'name',
        'brand',
        'product_category_id', // ✅ use correct column
        'sku',
        'description',
        'ingredients',
        'expiration_date',
        'price',
        'stock',
        'weight',
        'status'
    ];

    // 🔹 The SELLER who uploaded the product
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // 🔹 The CATEGORY = Pet Type (Dog, Cat, Bird, etc.)
    // this is linked via the pivot table product_pet_types
    public function petTypes()
    {
        return $this->belongsToMany(Category::class, 'product_pet_types', 'product_id', 'category_id');
    }

    // 🔹 The PRODUCT CATEGORY = (Food, Toys, Accessories, etc.)
    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

}
