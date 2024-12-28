<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductType extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'product_type';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];
}
