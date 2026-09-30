<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function deliveryPartner(): HasOne
    {
        return $this->hasOne(DeliveryPartner::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class);
    }

    public function reviewedApprovalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'reviewed_by');
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function reviewedPlatformReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewed_by');
    }

    public function changedOrderStatuses(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'changed_by');
    }

    public function designedOrderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'designer_id');
    }

    public function socialAccounts(): HasMany
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
            'customer' => 'customer.store',
            'designer' => 'designer.dashboard',
            'print_provider' => 'print-provider.dashboard',
            default => 'dashboard',
        };
    }

    /**
     * URL to send the user to after login: the page they originally asked for,
     * unless it belongs to another role (which would 403), else their dashboard.
     */
    public function postLoginUrl(\Illuminate\Http\Request $request): string
    {
        $default = route($this->dashboardRouteName(), absolute: false);
        $intended = $request->session()->pull('url.intended');

        if (! $intended) {
            return $default;
        }

        $section = explode('/', trim((string) parse_url($intended, PHP_URL_PATH), '/'))[0];
        $sectionRoles = [
            'admin' => 'admin',
            'customer' => 'customer',
            'designer' => 'designer',
            'print-provider' => 'print_provider',
        ];

        if (isset($sectionRoles[$section]) && ! $this->hasRole($sectionRoles[$section])) {
            return $default;
        }

        return $intended;
    }
}