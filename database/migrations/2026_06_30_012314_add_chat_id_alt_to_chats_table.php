<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            // Menyimpan JID alternatif (format @s.whatsapp.net) untuk kontak @lid
            // WAHA NOWEB hanya bisa kirim pesan ke @s.whatsapp.net, bukan @lid
            $table->string('chat_id_alt')->nullable()->after('chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn('chat_id_alt');
        });
    }
};
