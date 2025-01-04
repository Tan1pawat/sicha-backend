<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Bill extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'bill';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];
    protected $fillable = [
        'prison_id',
        'company_id',
        'date',
        'code',
        'sum_income',
        'sum_expense',
        'sum_total',
        'count',
    ];

    public function billOrders()
    {
        return $this->hasMany(BillOrder::class);
    }
}
