<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('design_favorites')) {
            Schema::create('design_favorites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('design_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'design_id'], 'design_favorite_user_design_unique');
                $table->index(['design_id', 'created_at'], 'design_favorite_design_created_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('design_favorites');
    }
};