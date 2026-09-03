<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->text('bio')->nullable();
            $table->json('skills')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('approval_status', 30)->default('draft')->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('total_sales')->default(0);
            $table->decimal('total_earnings', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('print_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp_number', 30)->nullable();
            $table->json('working_hours')->nullable();
            $table->string('license_document')->nullable();
            $table->string('verification_document')->nullable();
            $table->string('approval_status', 30)->default('draft')->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_earnings', 14, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

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
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('file_url')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_format', 30)->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->decimal('royalty_percentage', 5, 2)->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('total_sales')->default(0);
            $table->string('status', 30)->default('draft')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('design_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('designer_margin', 12, 2)->default(0);
            $table->decimal('position_x', 8, 3)->default(0);
            $table->decimal('position_y', 8, 3)->default(0);
            $table->decimal('scale', 8, 3)->default(1);
            $table->decimal('rotation', 8, 3)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['design_id', 'product_id']);
        });

        Schema::create('print_provider_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_custom')->default(false);
            $table->string('custom_name')->nullable();
            $table->text('custom_description')->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedSmallInteger('production_time')->nullable();
            $table->json('available_colors')->nullable();
            $table->json('available_sizes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['print_provider_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_provider_products');
        Schema::dropIfExists('design_products');
        Schema::dropIfExists('designs');
        Schema::dropIfExists('products');
        Schema::dropIfExists('delivery_partners');
        Schema::dropIfExists('print_providers');
        Schema::dropIfExists('designer_profiles');
    }
};
