<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A print shop is verified with a verification document and the manager's ID picture; the shop license is no longer
 * collected. The manager also gets their own contact email.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('print_providers')) {
            return;
        }

        if (Schema::hasColumn('print_providers', 'license_document')) {
            Schema::table('print_providers', function (Blueprint $table) {
                $table->dropColumn('license_document');
            });
        }

        if (! Schema::hasColumn('print_providers', 'id_document')) {
            Schema::table('print_providers', function (Blueprint $table) {
                $table->string('id_document')->nullable()->after('verification_document');
            });
        }

        if (! Schema::hasColumn('print_providers', 'contact_email')) {
            Schema::table('print_providers', function (Blueprint $table) {
                $table->string('contact_email')->nullable()->after('whatsapp_number');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('print_providers')) {
            return;
        }

        if (Schema::hasColumn('print_providers', 'contact_email')) {
            Schema::table('print_providers', function (Blueprint $table) {
                $table->dropColumn('contact_email');
            });
        }

        if (Schema::hasColumn('print_providers', 'id_document')) {
            Schema::table('print_providers', function (Blueprint $table) {
                $table->dropColumn('id_document');
            });
        }

        if (! Schema::hasColumn('print_providers', 'license_document')) {
            Schema::table('print_providers', function (Blueprint $table) {
                $table->string('license_document')->nullable()->after('working_hours');
            });
        }
    }
};
