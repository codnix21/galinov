<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $moderatorId = DB::table('roli')->where('kod', 'moderator')->value('id');
        if ($moderatorId === null) {
            return;
        }

        $realtorId = DB::table('roli')->where('kod', 'realtor')->value('id');
        if ($realtorId !== null) {
            DB::table('polzovateli')->where('rol_id', $moderatorId)->update(['rol_id' => $realtorId]);
        }

        DB::table('roli')->where('kod', 'moderator')->delete();
    }

    public function down(): void
    {
        $exists = DB::table('roli')->where('kod', 'moderator')->exists();
        if ($exists) {
            return;
        }

        DB::table('roli')->insert([
            'kod' => 'moderator',
            'nazvanie' => 'Модератор',
            'sozdano_at' => now(),
            'obnovleno_at' => now(),
        ]);
    }
};
