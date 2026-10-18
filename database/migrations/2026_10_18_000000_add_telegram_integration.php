<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A shop that connected the bot gets its new orders on Telegram; disconnecting clears the chat.
        Schema::table('print_providers', function (Blueprint $table) {
            $table->string('telegram_chat_id', 32)->nullable()->index()->after('whatsapp_opted_in_at');
            $table->string('telegram_username', 64)->nullable()->after('telegram_chat_id');
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_username');
        });

        // Every message the bot sent a shop about an order: the offer (with the buttons) and each attached file.
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('print_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('print_file_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose', 20); // offer | file
            $table->string('chat_id', 32)->index();
            $table->unsignedBigInteger('telegram_message_id')->nullable();
            $table->string('action_token', 40)->nullable()->unique(); // offer: carried in the button data
            $table->string('status', 12)->default('queued'); // queued, sent, failed
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'print_provider_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');

        Schema::table('print_providers', function (Blueprint $table) {
            $table->dropIndex(['telegram_chat_id']);
            $table->dropColumn(['telegram_chat_id', 'telegram_username', 'telegram_linked_at']);
        });
    }
};
