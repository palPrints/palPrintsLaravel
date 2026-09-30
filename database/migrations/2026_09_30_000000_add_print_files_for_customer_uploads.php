<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropForeign(['design_id']);
            });

            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('design_id')->nullable()->change();

                if (! Schema::hasColumn('cart_items', 'item_type')) {
                    $table->string('item_type', 30)->default('catalog_design')->after('design_id')->index();
                }

                $table->foreign('design_id')->references('id')->on('designs')->restrictOnDelete();
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropForeign(['design_id']);
                $table->dropForeign(['designer_id']);
            });

            Schema::table('order_items', function (Blueprint $table) {
                $table->foreignId('design_id')->nullable()->change();
                $table->foreignId('designer_id')->nullable()->change();

                if (! Schema::hasColumn('order_items', 'item_type')) {
                    $table->string('item_type', 30)->default('catalog_design')->after('designer_id')->index();
                }

                $table->foreign('design_id')->references('id')->on('designs')->restrictOnDelete();
                $table->foreign('designer_id')->references('id')->on('users')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('print_files')) {
            Schema::create('print_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->restrictOnDelete();
                $table->foreignId('cart_item_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
                $table->string('original_name');
                $table->string('stored_path');
                $table->string('disk', 50)->default('local');
                $table->string('mime_type', 120)->nullable();
                $table->string('extension', 20)->nullable();
                $table->unsignedBigInteger('file_size');
                $table->unsignedInteger('page_count')->nullable();
                $table->string('status', 30)->default('temporary')->index();
                $table->timestamp('uploaded_at')->nullable();
                $table->timestamp('attached_to_order_at')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'status'], 'print_file_user_status_index');
                $table->index(['cart_item_id', 'status'], 'print_file_cart_status_index');
                $table->index(['order_item_id', 'status'], 'print_file_order_status_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('print_files');

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (Schema::hasColumn('order_items', 'item_type')) {
                    $table->dropIndex(['item_type']);
                    $table->dropColumn('item_type');
                }

                $table->dropForeign(['design_id']);
                $table->dropForeign(['designer_id']);
            });

            Schema::table('order_items', function (Blueprint $table) {
                $table->foreignId('design_id')->nullable(false)->change();
                $table->foreignId('designer_id')->nullable(false)->change();
                $table->foreign('design_id')->references('id')->on('designs')->restrictOnDelete();
                $table->foreign('designer_id')->references('id')->on('users')->restrictOnDelete();
            });
        }

        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (Blueprint $table) {
                if (Schema::hasColumn('cart_items', 'item_type')) {
                    $table->dropIndex(['item_type']);
                    $table->dropColumn('item_type');
                }

                $table->dropForeign(['design_id']);
            });

            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('design_id')->nullable(false)->change();
                $table->foreign('design_id')->references('id')->on('designs')->restrictOnDelete();
            });
        }
    }
};