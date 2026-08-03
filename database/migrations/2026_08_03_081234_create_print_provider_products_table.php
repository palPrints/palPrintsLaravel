<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_provider_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_provider_id')->constrained('print_providers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->boolean('is_custom')->default(false);
            $table->string('custom_name')->nullable();
            $table->text('custom_description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('production_time');
            $table->json('available_colors')->nullable();
            $table->json('available_sizes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('print_provider_id');
            $table->index('product_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_provider_products');
    }
};
