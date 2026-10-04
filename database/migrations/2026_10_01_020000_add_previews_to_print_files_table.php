<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_files', function (Blueprint $table) {
            if (! Schema::hasColumn('print_files', 'preview_path')) {
                $table->string('preview_path')->nullable()->after('stored_path');
            }

            if (! Schema::hasColumn('print_files', 'preview_disk')) {
                $table->string('preview_disk', 50)->nullable()->after('preview_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('print_files', function (Blueprint $table) {
            if (Schema::hasColumn('print_files', 'preview_disk')) {
                $table->dropColumn('preview_disk');
            }

            if (Schema::hasColumn('print_files', 'preview_path')) {
                $table->dropColumn('preview_path');
            }
        });
    }
};