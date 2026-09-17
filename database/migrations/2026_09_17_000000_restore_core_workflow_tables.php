<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureOnboardingColumns('designer_profiles');
        $this->ensureOnboardingColumns('print_providers');

        if (! Schema::hasTable('approval_requests')) {
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
        }

        if (! Schema::hasTable('user_notifications')) {
            Schema::create('user_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('type', 80)->index();
                $table->string('title');
                $table->text('message');
                $table->string('link')->nullable();
                $table->boolean('is_read')->default(false)->index();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->decimal('total_balance', 14, 2)->default(0);
                $table->decimal('available_balance', 14, 2)->default(0);
                $table->decimal('pending_balance', 14, 2)->default(0);
                $table->decimal('total_withdrawn', 14, 2)->default(0);
                $table->timestamp('last_updated')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->string('type', 40)->index();
                $table->decimal('amount', 14, 2);
                $table->decimal('balance_after', 14, 2);
                $table->text('description')->nullable();
                $table->string('status', 30)->default('completed')->index();
                $table->string('reference_id')->nullable()->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('withdrawal_requests')) {
            Schema::create('withdrawal_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->restrictOnDelete();
                $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
                $table->decimal('amount', 14, 2);
                $table->string('method', 50);
                $table->json('account_details');
                $table->string('status', 30)->default('pending')->index();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('reference_id')->nullable()->unique();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('approval_requests');
    }

    private function ensureOnboardingColumns(string $tableName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (! Schema::hasColumn($tableName, 'profile_completed_at')) {
                $table->timestamp('profile_completed_at')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }

            if (! Schema::hasColumn($tableName, 'admin_notes')) {
                $table->text('admin_notes')->nullable();
            }
        });
    }
};
