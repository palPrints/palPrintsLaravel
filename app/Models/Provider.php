<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'phone', 'email', 'license_number', 'status'];
    public function offerings(): HasMany { return $this->hasMany(ProviderOffering::class); }
}
