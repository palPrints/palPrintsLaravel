<?php

namespace App\Models;

use App\Services\WhatsApp\PhoneNumber;
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
        'whatsapp_notifications_enabled',
        'whatsapp_opted_in_at',
        'telegram_chat_id',
        'telegram_username',
        'telegram_linked_at',
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
        'whatsapp_notifications_enabled' => 'boolean',
        'whatsapp_opted_in_at' => 'datetime',
        'telegram_linked_at' => 'datetime',
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

    /** The shop's WhatsApp number in international digits when it agreed to get orders there and may receive them, else null. */
    public function whatsappRecipient(): ?string
    {
        if (! $this->whatsapp_notifications_enabled || ! $this->is_active || $this->approval_status !== 'approved') {
            return null;
        }

        return PhoneNumber::normalize($this->whatsapp_number);
    }

    /** The Telegram chat that gets the shop's new orders, when it connected the bot and may receive orders, else null. */
    public function telegramRecipient(): ?string
    {
        if (! $this->is_active || $this->approval_status !== 'approved') {
            return null;
        }

        return filled($this->telegram_chat_id) ? (string) $this->telegram_chat_id : null;
    }

    /** The WhatsApp number as shown to people: +970599123456 once normalised, else as it was typed before normalising existed. */
    public function whatsappDisplayNumber(): ?string
    {
        $number = PhoneNumber::normalize($this->whatsapp_number);

        return $number !== null ? '+'.$number : $this->whatsapp_number;
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

