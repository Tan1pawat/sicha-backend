<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'product';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }
}
