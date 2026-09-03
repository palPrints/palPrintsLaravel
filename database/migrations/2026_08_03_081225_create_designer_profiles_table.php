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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();
            $table->string('full_name');
            $table->text('bio')->nullable();
            $table->json('skills')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('profile_image')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->integer('total_sales')->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0.00);
            $table->timestamps();

            $table->index('approval_status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designer_profiles');
    }
};
