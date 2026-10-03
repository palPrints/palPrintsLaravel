<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PrintProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'address',
        'phone',
        'whatsapp_number',
        'contact_email',
        'services',
        'working_hours',
        'verification_document',
        'id_document',
        'approval_status',
        'profile_completed_at',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'admin_notes',
        'total_orders',
        'total_earnings',
        'is_active',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'services' => 'array',
        'profile_completed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'total_earnings' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ========== ط§ظ„ط¹ظ„ط§ظ‚ط§طھ ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public function branches()
    {
        return $this->hasMany(PrintProviderBranch::class);
    }

    /** The shop's main branch (its offerings live there); created from the profile data when it has none yet. */
    public function primaryBranch(): PrintProviderBranch
    {
        return $this->branches()->orderBy('id')->first() ?? PrintProviderBranch::create([
            'print_provider_id' => $this->id,
            'name' => $this->company_name,
            'city' => Str::limit(trim(explode('،', str_replace(',', '،', (string) $this->address))[0]) ?: 'غير محدد', 100, ''),
            'address' => $this->address,
            'phone' => $this->phone,
            'working_hours' => $this->working_hours,
            'is_active' => true,
        ]);
    }
}

