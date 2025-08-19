<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EbayMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'sku',
        'ebay_l30',
        'ebay_l60',
        'ebay_price',
    ];
}
