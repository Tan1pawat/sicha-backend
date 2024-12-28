<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class BillOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'bill_order';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];
    protected $fillable = [
        'bill_id',
        'product_id',
        'price',
        'value',
    ];
}
