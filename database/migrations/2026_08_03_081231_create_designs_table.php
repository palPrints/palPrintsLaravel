<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designer_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url');
            $table->string('file_url');
            $table->integer('file_size')->nullable();
            $table->enum('file_format', ['jpg', 'jpeg', 'png', 'svg', 'pdf', 'ai', 'psd']);
            $table->decimal('base_price', 10, 2)->default(0.00);
            $table->decimal('royalty_percentage', 5, 2)->default(0.00);
            $table->decimal('avg_rating', 3, 2)->default(0.00);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_sales')->default(0);
            $table->enum('status', ['draft', 'pending', 'published', 'rejected', 'hidden'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('designer_id');
            $table->index('status');
            $table->index('avg_rating');
            $table->index('created_at');
            $table->fullText(['title', 'description']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designs');
    }
};
