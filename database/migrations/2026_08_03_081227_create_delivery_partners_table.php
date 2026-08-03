<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();
            $table->string('company_name');
            $table->string('contact_person');
            $table->string('phone');
            $table->string('email');
            $table->json('service_areas');
            $table->decimal('delivery_fee', 10, 2)->default(0.00);
            $table->integer('estimated_delivery_days')->default(3);
            $table->string('api_key')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('approval_status');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_partners');
    }
};
