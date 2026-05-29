<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polzovateli', function (Blueprint $table) {
            if (!Schema::hasColumn('polzovateli', 'telegram_chat_id')) {
                $table->string('telegram_chat_id', 32)->nullable()->after('telefon');
            }
        });

        Schema::create('zhurnal_napominaniy', function (Blueprint $table) {
            $table->id();
            $table->string('tip', 64);
            $table->string('entity_type', 32);
            $table->unsignedBigInteger('entity_id');
            $table->date('den');
            $table->timestamp('sozdano_at')->useCurrent();
            $table->unique(['tip', 'entity_type', 'entity_id', 'den']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zhurnal_napominaniy');

        Schema::table('polzovateli', function (Blueprint $table) {
            if (Schema::hasColumn('polzovateli', 'telegram_chat_id')) {
                $table->dropColumn('telegram_chat_id');
            }
        });
    }
};
