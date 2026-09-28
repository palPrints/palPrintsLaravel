<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('print_provider_branches')) {
            Schema::create('print_provider_branches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('print_provider_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('city');
                $table->string('region')->nullable();
                $table->text('address')->nullable();
                $table->string('phone', 30)->nullable();
                $table->json('working_hours')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->index(['print_provider_id', 'is_active'], 'provider_branch_active_index');
            });
        }

        if (! Schema::hasTable('branch_product_offerings')) {
            Schema::create('branch_product_offerings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('print_provider_branch_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->decimal('base_price', 12, 2);
                $table->char('currency', 3)->default('ILS');
                $table->unsignedInteger('production_time_min');
                $table->unsignedInteger('production_time_max');
                $table->unsignedInteger('daily_capacity');
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->unique(['print_provider_branch_id', 'product_id'], 'branch_product_unique');
                $table->index(['product_id', 'is_active'], 'branch_offering_product_active_index');
            });
        }

        if (! Schema::hasTable('addresses')) {
            Schema::create('addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('city');
                $table->string('region')->nullable();
                $table->string('street');
                $table->string('building')->nullable();
                $table->string('apartment')->nullable();
                $table->string('phone', 30);
                $table->boolean('is_default')->default(false)->index();
                $table->boolean('is_deleted')->default(false)->index();
                $table->timestamps();
                $table->index(['user_id', 'is_deleted'], 'address_user_deleted_index');
            });
        }

        if (! Schema::hasTable('carts')) {
            Schema::create('carts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('status', 30)->default('active')->index();
                $table->timestamp('converted_at')->nullable();
                $table->timestamp('abandoned_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'status'], 'cart_user_status_index');
            });
        }

        if (! Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->foreignId('variant_id')->constrained()->restrictOnDelete();
                $table->foreignId('design_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('unit_price', 12, 2);
                $table->json('selected_options')->nullable();
                $table->timestamps();
                $table->unique(['cart_id', 'product_id', 'variant_id', 'design_id'], 'cart_product_variant_design_unique');
            });
        }

        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->restrictOnDelete();
                $table->string('order_number', 40)->unique();
                $table->foreignId('shipping_address_id')->nullable()->constrained('addresses')->nullOnDelete();
                $table->json('shipping_address_snapshot')->nullable();
                $table->string('status', 30)->default('processing')->index();
                $table->string('payment_status', 30)->default('paid')->index();
                $table->string('payment_method', 50)->nullable();
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('shipping_cost', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'status'], 'order_user_status_index');
            });
        }

        if (! Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->foreignId('variant_id')->constrained()->restrictOnDelete();
                $table->foreignId('design_id')->constrained()->restrictOnDelete();
                $table->foreignId('designer_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('print_provider_branch_id')->constrained()->restrictOnDelete();
                $table->foreignId('branch_product_offering_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('total_price', 14, 2);
                $table->decimal('provider_cost', 12, 2)->default(0);
                $table->decimal('designer_profit', 12, 2)->default(0);
                $table->decimal('platform_commission', 12, 2)->default(0);
                $table->json('selected_options')->nullable();
                $table->timestamps();
                $table->index(['order_id', 'product_id'], 'order_item_product_index');
                $table->index(['designer_id', 'created_at'], 'order_item_designer_created_index');
                $table->index(['print_provider_branch_id', 'created_at'], 'order_item_branch_created_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('branch_product_offerings');
        Schema::dropIfExists('print_provider_branches');
    }
};
