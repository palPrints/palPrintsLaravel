<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the legacy application-domain tables.
     *
     * Authentication, registration, account profiles, roles, sessions,
     * social accounts, cache/queue infrastructure, and audit logs remain.
     */
    public function up(): void
    {
        $tables = [
            'approval_requests',
            'withdrawal_requests',
            'wallet_transactions',
            'wallets',
            'reviews',
            'order_items',
            'orders',
            'carts',
            'addresses',
            'print_provider_products',
            'design_products',
            'designs',
            'products',
            'delivery_partners',
            'system_settings',
            'user_notifications',
            'notifications',
        ];

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                Schema::dropIfExists($table);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * Dropped application data cannot be restored safely.
     */
    public function down(): void
    {
        // Intentionally irreversible.
    }
};
