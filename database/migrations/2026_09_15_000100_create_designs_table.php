<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('designs')) {
            Schema::table('designs', function (Blueprint $table) {
                if (! Schema::hasColumn('designs', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable()->after('design_payload');
                }
            });

            return;
        }

        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('designer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('designer_profit', 12, 2)->default(0);
            $table->json('selected_options')->nullable();
            $table->json('design_payload')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status', 'published_at']);
            $table->index(['designer_id', 'status']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('designs') && Schema::hasColumn('designs', 'rejection_reason')) {
            Schema::table('designs', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
        }
    }
};
