<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('data_type');
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('code');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['attribute_id', 'code']);
        });

        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->restrictOnDelete();
            $table->boolean('is_variant_axis')->default(false);
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['product_id', 'attribute_id']);
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->unique(['product_attribute_id', 'attribute_value_id'], 'product_attribute_value_unique');
        });

        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::create('variant_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_value_id')->constrained()->restrictOnDelete();
            $table->unique(['variant_id', 'product_attribute_value_id'], 'variant_attribute_value_unique');
        });

        Schema::create('printing_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->unique();
            $table->string('license_number')->unique();
            $table->string('status')->index();
        });

        Schema::create('provider_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('base_price', 12, 2);
            $table->char('currency', 3);
            $table->unsignedInteger('production_time_min');
            $table->unsignedInteger('production_time_max');
            $table->unsignedInteger('daily_capacity');
            $table->boolean('is_active')->default(true)->index();
            $table->unique(['provider_id', 'product_id']);
        });

        Schema::create('provider_offering_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained()->cascadeOnDelete();
            $table->string('provider_sku')->nullable();
            $table->boolean('is_available')->default(true)->index();
            $table->unique(['provider_offering_id', 'variant_id'], 'offering_variant_unique');
            $table->unique(['provider_offering_id', 'provider_sku'], 'offering_provider_sku_unique');
        });

        Schema::create('print_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_offering_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->decimal('max_width_mm', 8, 2);
            $table->decimal('max_height_mm', 8, 2);
            $table->boolean('is_active')->default(true)->index();
            $table->unique(['provider_offering_id', 'code']);
        });

        Schema::create('print_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_area_id')->constrained()->cascadeOnDelete();
            $table->foreignId('printing_method_id')->constrained()->restrictOnDelete();
            $table->boolean('applies_to_all_variants')->default(true);
            $table->decimal('max_width_mm', 8, 2);
            $table->decimal('max_height_mm', 8, 2);
            $table->boolean('is_active')->default(true)->index();
            $table->unique(['print_area_id', 'printing_method_id']);
        });

        Schema::create('print_capability_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_capability_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_offering_variant_id')->constrained()->cascadeOnDelete();
            $table->unique(['print_capability_id', 'provider_offering_variant_id'], 'capability_offering_variant_unique');
        });

        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_offering_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('print_capability_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('min_quantity');
            $table->unsignedInteger('max_quantity')->nullable();
            $table->string('pricing_type');
            $table->decimal('amount', 12, 2);
            $table->integer('priority')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('print_capability_variants');
        Schema::dropIfExists('print_capabilities');
        Schema::dropIfExists('print_areas');
        Schema::dropIfExists('provider_offering_variants');
        Schema::dropIfExists('provider_offerings');
        Schema::dropIfExists('providers');
        Schema::dropIfExists('printing_methods');
        Schema::dropIfExists('variant_values');
        Schema::dropIfExists('variants');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
