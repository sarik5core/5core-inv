<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemuProductSheet extends Model
{
    protected $table = 'temu_product_sheets';

    protected $fillable = [
        'sku', 'price', 'pft', 'roi', 'l30', 'dil', 'buy_link','l60'
    ];
    use HasFactory;
}
