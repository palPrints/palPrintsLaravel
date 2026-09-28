<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('printing_methods')) {
            Schema::create('printing_methods', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('branch_offering_variants')) {
            Schema::create('branch_offering_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_product_offering_id')->constrained(indexName: 'bov_offering_fk')->cascadeOnDelete();
                $table->foreignId('variant_id')->constrained(indexName: 'bov_variant_fk')->restrictOnDelete();
                $table->string('branch_sku')->nullable();
                $table->boolean('is_available')->default(true)->index();
                $table->timestamps();
                $table->unique(['branch_product_offering_id', 'variant_id'], 'branch_offering_variant_unique');
                $table->index(['variant_id', 'is_available'], 'branch_variant_available_index');
            });
        }

        if (! Schema::hasTable('branch_print_areas')) {
            Schema::create('branch_print_areas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_product_offering_id')->constrained(indexName: 'bpa_offering_fk')->cascadeOnDelete();
                $table->string('code');
                $table->string('name');
                $table->unsignedInteger('max_width_mm')->nullable();
                $table->unsignedInteger('max_height_mm')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->unique(['branch_product_offering_id', 'code'], 'branch_print_area_code_unique');
            });
        }

        if (! Schema::hasTable('branch_print_capabilities')) {
            Schema::create('branch_print_capabilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_print_area_id')->constrained(indexName: 'bpc_area_fk')->cascadeOnDelete();
                $table->foreignId('printing_method_id')->constrained(indexName: 'bpc_method_fk')->restrictOnDelete();
                $table->boolean('applies_to_all_variants')->default(true)->index();
                $table->unsignedInteger('max_width_mm')->nullable();
                $table->unsignedInteger('max_height_mm')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->unique(['branch_print_area_id', 'printing_method_id'], 'branch_print_capability_unique');
            });
        }

        if (! Schema::hasTable('branch_print_capability_variants')) {
            Schema::create('branch_print_capability_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_print_capability_id')->constrained(indexName: 'bpcv_capability_fk')->cascadeOnDelete();
                $table->foreignId('branch_offering_variant_id')->constrained(indexName: 'bpcv_variant_fk')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(
                    ['branch_print_capability_id', 'branch_offering_variant_id'],
                    'branch_capability_variant_unique'
                );
            });
        }

        if (! Schema::hasTable('branch_pricing_rules')) {
            Schema::create('branch_pricing_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_product_offering_id')->constrained(indexName: 'bpr_offering_fk')->cascadeOnDelete();
                $table->foreignId('branch_offering_variant_id')->nullable()->constrained(indexName: 'bpr_variant_fk')->cascadeOnDelete();
                $table->foreignId('branch_print_capability_id')->nullable()->constrained(indexName: 'bpr_capability_fk')->cascadeOnDelete();
                $table->unsignedInteger('min_quantity')->default(1);
                $table->unsignedInteger('max_quantity')->nullable();
                $table->string('pricing_type', 30)->default('base');
                $table->string('value_type', 30)->default('fixed');
                $table->decimal('amount', 12, 2);
                $table->unsignedInteger('priority')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('valid_from')->nullable();
                $table->timestamp('valid_until')->nullable();
                $table->timestamps();
                $table->index(['branch_product_offering_id', 'is_active'], 'branch_pricing_offering_active_index');
                $table->index(['pricing_type', 'priority'], 'branch_pricing_type_priority_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_pricing_rules');
        Schema::dropIfExists('branch_print_capability_variants');
        Schema::dropIfExists('branch_print_capabilities');
        Schema::dropIfExists('branch_print_areas');
        Schema::dropIfExists('branch_offering_variants');
        Schema::dropIfExists('printing_methods');
    }
};