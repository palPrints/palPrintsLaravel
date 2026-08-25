<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications') && ! Schema::hasTable('user_notifications')) {
            Schema::rename('notifications', 'user_notifications');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_notifications') && ! Schema::hasTable('notifications')) {
            Schema::rename('user_notifications', 'notifications');
        }
    }
};
