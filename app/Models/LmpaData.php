<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LmpaData extends Model
{
    protected $connection = 'repricer';
    protected $table = 'lmpa_data';
    public $timestamps = false; // यदि timestamps नहीं हैं
    protected $fillable = ['sku', 'epid', 'price'];
}
