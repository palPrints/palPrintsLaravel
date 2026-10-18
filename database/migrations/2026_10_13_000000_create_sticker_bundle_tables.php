<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sticker_categories')) {
            Schema::create('sticker_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);
                $table->string('slug', 100)->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sticker_library_items')) {
            Schema::create('sticker_library_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sticker_category_id')->constrained()->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('image_path');
                $table->string('disk', 50)->default('public');
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->timestamps();
                $table->index(['sticker_category_id', 'is_active', 'sort_order'], 'sticker_library_category_active_order');
            });
        }

        if (! Schema::hasTable('sticker_settings')) {
            Schema::create('sticker_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 80)->unique();
                $table->json('value');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sticker_bundle_lines')) {
            Schema::create('sticker_bundle_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cart_item_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('order_item_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('source', 20);
                $table->string('client_id', 100)->nullable();
                $table->string('upload_key', 100)->nullable();
                $table->foreignId('sticker_library_item_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('print_file_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedInteger('quantity');
                $table->string('size_key', 40);
                $table->decimal('width_mm', 8, 2);
                $table->decimal('height_mm', 8, 2);
                $table->string('cut_type', 30)->default('circle');
                $table->string('label', 120)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['cart_item_id', 'sort_order'], 'sticker_bundle_cart_order');
                $table->index(['order_item_id', 'sort_order'], 'sticker_bundle_order_order');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sticker_bundle_lines');
        Schema::dropIfExists('sticker_settings');
        Schema::dropIfExists('sticker_library_items');
        Schema::dropIfExists('sticker_categories');
    }
};
