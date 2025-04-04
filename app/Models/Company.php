<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Company extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'company';
    protected $softDelete = true;

    protected $hidden = ['deleted_at'];
}
