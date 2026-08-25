<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar_path',
        'locale',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function designerProfile()
    {
        return $this->hasOne(DesignerProfile::class);
    }

    public function printProvider()
    {
        return $this->hasOne(PrintProvider::class);
    }

    public function deliveryPartner()
    {
        return $this->hasOne(DeliveryPartner::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function designs()
    {
        return $this->hasMany(Design::class, 'designer_id');
    }

    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function approvalRequests()
    {
        return $this->hasMany(ApprovalRequest::class);
    }

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function primaryRole(): ?string
    {
        return $this->getRoleNames()->first();
    }

    public function supportsOnboarding(): bool
    {
        return in_array($this->primaryRole(), ['designer', 'print_provider', 'delivery_partner'], true);
    }

    public function roleProfile(): ?Model
    {
        return match ($this->primaryRole()) {
            'designer' => $this->designerProfile,
            'print_provider' => $this->printProvider,
            'delivery_partner' => $this->deliveryPartner,
            default => null,
        };
    }

    public function approvalStatus(): string
    {
        return $this->supportsOnboarding()
            ? ($this->roleProfile()?->approval_status ?? 'draft')
            : 'approved';
    }

    public function hasCompletedRoleProfile(): bool
    {
        return ! $this->supportsOnboarding()
            || $this->roleProfile()?->profile_completed_at !== null;
    }

    public function hasApprovedBusinessAccount(): bool
    {
        return ! $this->supportsOnboarding() || $this->approvalStatus() === 'approved';
    }

    public function isAwaitingApproval(): bool
    {
        return $this->supportsOnboarding()
            && in_array($this->approvalStatus(), ['submitted', 'under_review'], true);
    }

    public function dashboardRouteName(): string
    {
        return match ($this->primaryRole()) {
            'admin' => 'admin.dashboard',
            'customer' => 'customer.dashboard',
            'designer' => 'designer.dashboard',
            'print_provider' => 'print-provider.dashboard',
            'delivery_partner' => 'delivery-partner.dashboard',
            default => 'dashboard',
        };
    }
}
