<?php

// app/Models/Permission.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','role','permissions','culomn_permission'];

    protected $casts = [
        'permissions' => 'array',
        'culomn_permission' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}