<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDocument extends Model
{
    protected $fillable = ['product_id','document_type','file_path'];
}
