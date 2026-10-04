<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DesignerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'bio',
        'skills',
        'portfolio_url',
        'profile_image',
        'approval_status',
        'profile_completed_at',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'admin_notes',
        'total_sales',
        'total_earnings',
    ];

    protected $casts = [
        'skills' => 'array',
        'profile_completed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'total_earnings' => 'decimal:2',
    ];

    /**
     * Required profile data that is missing from what is saved right now, keyed by field.
     *
     * @return array<string, string>
     */
    public function missingRequiredFields(): array
    {
        $missing = [];

        if (blank($this->full_name)) {
            $missing['full_name'] = 'الاسم الكامل';
        }

        if (blank($this->bio)) {
            $missing['bio'] = 'النبذة التعريفية';
        }

        if (collect($this->skills ?? [])->filter()->isEmpty()) {
            $missing['skills'] = 'المهارات';
        }

        if (! filter_var($this->portfolio_url, FILTER_VALIDATE_URL)) {
            $missing['portfolio_url'] = 'رابط معرض الأعمال';
        }

        return $missing;
    }

    public function isComplete(): bool
    {
        return $this->missingRequiredFields() === [];
    }

    // ========== العلاقات ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
