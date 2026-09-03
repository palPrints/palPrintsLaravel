<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();
            $table->string('company_name');
            $table->text('address');
            $table->string('phone');
            $table->string('whatsapp_number');
            $table->string('working_hours')->nullable();
            $table->string('license_document')->nullable();
            $table->string('verification_document')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->integer('total_orders')->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('approval_status');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_providers');
    }
};
