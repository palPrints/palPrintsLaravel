<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_providers', function (Blueprint $table) {
            $table->boolean('whatsapp_notifications_enabled')->default(false)->after('whatsapp_number');
            $table->timestamp('whatsapp_opted_in_at')->nullable()->after('whatsapp_notifications_enabled');
        });

        // Every message sent to or received from a shop on WhatsApp. The database stays the source of truth.
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 3); // out | in
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('print_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose', 30)->nullable(); // offer | reply (out)
            $table->string('wa_id', 20)->index(); // the shop's number, digits only
            $table->string('wa_message_id')->nullable()->unique(); // Meta's wamid; unique so a replayed webhook is ignored
            $table->string('context_wa_message_id')->nullable()->index(); // inbound: the message being answered
            $table->string('action_token', 40)->nullable()->unique(); // offer: carried in the quick-reply payloads
            $table->string('type', 20); // template | text | button | ...
            $table->json('payload')->nullable(); // request body (out) or message object (in), never secrets
            $table->string('status', 12)->default('queued'); // queued, accepted, sent, delivered, read, failed (out); received (in)
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');

        Schema::table('print_providers', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_notifications_enabled', 'whatsapp_opted_in_at']);
        });
    }
};
