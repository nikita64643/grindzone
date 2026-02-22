<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CHAT_PERMS = ['essentials.chat.color', 'essentials.chat.format'];

    public function up(): void
    {
        $privileges = DB::table('privileges')->get();
        foreach ($privileges as $p) {
            $perms = is_string($p->lp_permissions)
                ? json_decode($p->lp_permissions, true)
                : $p->lp_permissions;
            if (! is_array($perms)) {
                $perms = [];
            }
            $changed = false;
            foreach (self::CHAT_PERMS as $perm) {
                if (! in_array($perm, $perms, true)) {
                    $perms[] = $perm;
                    $changed = true;
                }
            }
            if ($changed) {
                DB::table('privileges')->where('id', $p->id)->update([
                    'lp_permissions' => json_encode(array_values($perms)),
                ]);
            }
        }
    }

    public function down(): void
    {
        $privileges = DB::table('privileges')->get();
        foreach ($privileges as $p) {
            $perms = is_string($p->lp_permissions)
                ? json_decode($p->lp_permissions, true)
                : $p->lp_permissions;
            if (! is_array($perms)) {
                continue;
            }
            $perms = array_values(array_filter($perms, fn($x) => ! in_array($x, self::CHAT_PERMS, true)));
            DB::table('privileges')->where('id', $p->id)->update([
                'lp_permissions' => json_encode($perms),
            ]);
        }
    }
};
