<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The designer profile page collects a job title and years of experience; both were shown in the form but had no
 * column to be saved in.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('designer_profiles')) {
            return;
        }

        if (! Schema::hasColumn('designer_profiles', 'job_title')) {
            Schema::table('designer_profiles', function (Blueprint $table) {
                $table->string('job_title', 120)->nullable()->after('full_name');
            });
        }

        if (! Schema::hasColumn('designer_profiles', 'experience')) {
            Schema::table('designer_profiles', function (Blueprint $table) {
                $table->string('experience', 60)->nullable()->after('job_title');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('designer_profiles')) {
            return;
        }

        if (Schema::hasColumn('designer_profiles', 'experience')) {
            Schema::table('designer_profiles', function (Blueprint $table) {
                $table->dropColumn('experience');
            });
        }

        if (Schema::hasColumn('designer_profiles', 'job_title')) {
            Schema::table('designer_profiles', function (Blueprint $table) {
                $table->dropColumn('job_title');
            });
        }
    }
};
