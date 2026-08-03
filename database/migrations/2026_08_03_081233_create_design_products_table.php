<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained('designs')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('designer_margin', 10, 2)->default(0.00);
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->integer('scale')->default(100);
            $table->integer('rotation')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['design_id', 'product_id']);
            $table->index('design_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_products');
    }
};
