<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\Securities\Price;

class TiktokSheet extends Model
{
    use HasFactory;
    protected $table = 'tiktok_sheet_data';

    protected $fillable = [
        'id',
        'sku',
        'price',
        'l30',
        'l60',
        'views',
    ];

}
