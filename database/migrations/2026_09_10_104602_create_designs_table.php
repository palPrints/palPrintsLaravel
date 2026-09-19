<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('designs', function (Blueprint $table) {
        $table->id();

        // المصمم صاحب التصميم
        $table->foreignId('designer_id')
            ->constrained('users')
            ->cascadeOnDelete();

        // بيانات التصميم
        $table->string('title');
        $table->text('description')->nullable();

        // صورة التصميم
        $table->string('image')->nullable();

        // حالة التصميم
        $table->enum('status', [
            'draft',
            'review',
            'published',
            'rejected'
        ])->default('draft');

        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};
