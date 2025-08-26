<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RfqForm extends Model
{
    protected $fillable = [
        'category_id', 'rfq_form_name', 'slug', 'title', 'subtitle', 'main_image'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->slug = Str::slug($model->rfq_form_name.'-'.time());
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

}

