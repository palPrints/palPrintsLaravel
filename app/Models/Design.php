<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Design extends Model
{
    protected $fillable = [
        'designer_id',
        'title',
        'description',
        'image',
        'status',
    ];
}