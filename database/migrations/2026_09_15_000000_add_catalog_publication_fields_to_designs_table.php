<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('designer_id')->constrained()->nullOnDelete();
            $table->decimal('base_price', 12, 2)->default(0)->after('image');
            $table->decimal('selling_price', 12, 2)->default(0)->after('base_price');
            $table->decimal('designer_profit', 12, 2)->default(0)->after('selling_price');
            $table->json('selected_options')->nullable()->after('designer_profit');
            $table->json('design_payload')->nullable()->after('selected_options');
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->timestamp('published_at')->nullable()->after('reviewed_at');

            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'status']);
            $table->dropForeign(['product_id']);
            $table->dropColumn([
                'product_id',
                'base_price',
                'selling_price',
                'designer_profit',
                'selected_options',
                'design_payload',
                'submitted_at',
                'reviewed_at',
                'published_at',
            ]);
        });
    }
};
