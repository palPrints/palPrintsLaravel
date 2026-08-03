<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('design_product_id')->constrained('design_products')->onDelete('restrict');
            $table->integer('quantity');
            $table->string('selected_color')->nullable();
            $table->string('selected_size')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->decimal('designer_earnings', 10, 2)->default(0.00);
            $table->decimal('print_provider_earnings', 10, 2)->default(0.00);
            $table->decimal('platform_commission', 10, 2)->default(0.00);
            $table->timestamps();

            $table->index('order_id');
            $table->index('design_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
