<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemuProductSheet extends Model
{
    protected $fillable = [
        'sku', 'price', 'pft', 'roi', 'l30', 'dil', 'buy_link'
    ];
    use HasFactory;
}
