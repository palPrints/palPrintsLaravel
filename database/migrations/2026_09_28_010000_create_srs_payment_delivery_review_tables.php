<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_partners')) {
            Schema::create('delivery_partners', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('company_name');
                $table->string('contact_person')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('email')->nullable();
                $table->json('service_areas')->nullable();
                $table->decimal('delivery_fee', 12, 2)->default(0);
                $table->unsignedSmallInteger('estimated_delivery_days')->nullable();
                $table->string('api_key', 100)->nullable()->unique();
                $table->string('approval_status', 30)->default('draft')->index();
                $table->timestamp('profile_completed_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('rejection_reason')->nullable();
                $table->text('admin_notes')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->restrictOnDelete();
                $table->string('payment_number', 40)->unique();
                $table->string('status', 30)->default('pending')->index();
                $table->string('method', 50)->nullable();
                $table->string('gateway', 50)->nullable();
                $table->string('gateway_transaction_id')->nullable()->index();
                $table->decimal('amount', 14, 2);
                $table->char('currency', 3)->default('ILS');
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('refunded_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->json('gateway_payload')->nullable();
                $table->timestamps();
                $table->index(['order_id', 'status'], 'payment_order_status_index');
                $table->index(['user_id', 'created_at'], 'payment_user_created_index');
            });
        }

        if (! Schema::hasTable('order_status_history')) {
            Schema::create('order_status_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30);
                $table->text('note')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['order_id', 'created_at'], 'order_status_history_order_created_index');
            });
        }

        if (! Schema::hasTable('shipments')) {
            Schema::create('shipments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('delivery_partner_id')->nullable()->constrained()->nullOnDelete();
                $table->string('shipment_number', 40)->unique();
                $table->string('tracking_number')->nullable()->index();
                $table->string('status', 30)->default('pending')->index();
                $table->decimal('shipping_cost', 12, 2)->default(0);
                $table->char('currency', 3)->default('ILS');
                $table->json('delivery_address_snapshot')->nullable();
                $table->string('tracking_url')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('estimated_delivery_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['delivery_partner_id', 'status'], 'shipment_partner_status_index');
            });
        }

        if (! Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->string('title')->nullable();
                $table->text('comment')->nullable();
                $table->string('status', 30)->default('pending')->index();
                $table->boolean('is_featured')->default(false)->index();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'created_at'], 'review_user_created_index');
                $table->index(['order_id', 'status'], 'review_order_status_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('delivery_partners');
    }
};