<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            DB::table('products')
                ->where('code', 'PAPER-PRINT')
                ->update(['is_active' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            DB::table('products')
                ->where('code', 'PAPER-PRINT')
                ->update(['is_active' => false]);
        }
    }
};