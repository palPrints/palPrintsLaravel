<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('custom_fonts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 160);
            $table->string('family', 160);
            $table->string('format', 8);
            $table->string('file_path');
            $table->string('disk', 40)->default('local');
            $table->string('file_hash', 64)->unique();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->boolean('is_active')->default(true);
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'family']);
        });
    }

    public function down(): void { Schema::dropIfExists('custom_fonts'); }
};
