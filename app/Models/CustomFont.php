<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFont extends Model
{
    protected $fillable = ['uploaded_by', 'name', 'family', 'format', 'file_path', 'disk', 'file_hash', 'mime_type', 'file_size', 'is_active', 'suspended_at'];
    protected $casts = ['is_active' => 'boolean', 'suspended_at' => 'datetime', 'file_size' => 'integer'];

    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
