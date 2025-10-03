<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FbaInventory extends Model
{
    use HasFactory;

    protected $table = 'fba_inventory';

    protected $fillable = [
        'sku',
        'asin',
        'price',
        'units_ordered_l30',
        'sessions_l30',
        'units_ordered_l60',
        'sessions_l60',
        'original_fba_sku',
        'buy_box_price'
    ];
}
