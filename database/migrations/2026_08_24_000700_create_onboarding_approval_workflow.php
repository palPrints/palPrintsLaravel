<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designer_profiles', function (Blueprint $table) {
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
        });

        Schema::table('print_providers', function (Blueprint $table) {
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
        });

        Schema::table('delivery_partners', function (Blueprint $table) {
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
        });

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 40)->index();
            $table->string('request_number', 60)->unique();
            $table->string('status', 30)->default('submitted')->index();
            $table->json('profile_snapshot')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'role', 'status']);
        });

        DB::table('designer_profiles')->where('approval_status', 'pending')->update(['approval_status' => 'draft']);
        DB::table('print_providers')->where('approval_status', 'pending')->update(['approval_status' => 'draft']);
        DB::table('delivery_partners')->where('approval_status', 'pending')->update(['approval_status' => 'draft']);
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');

        Schema::table('delivery_partners', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['profile_completed_at', 'submitted_at', 'reviewed_at', 'approved_at', 'approved_by', 'rejection_reason', 'admin_notes']);
        });

        Schema::table('print_providers', function (Blueprint $table) {
            $table->dropColumn(['profile_completed_at', 'submitted_at', 'reviewed_at', 'admin_notes']);
        });

        Schema::table('designer_profiles', function (Blueprint $table) {
            $table->dropColumn(['profile_completed_at', 'submitted_at', 'reviewed_at', 'admin_notes']);
        });
    }
};
