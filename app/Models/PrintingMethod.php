<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrintingMethod extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'code', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function printCapabilities(): HasMany { return $this->hasMany(PrintCapability::class); }
}
