<?php

namespace Database\Seeders;

use App\Models\MinecraftServer;
use App\Models\PrivilegeServer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MinecraftServerSeeder extends Seeder
{
    public function run(): void
    {
        $servers = config('minecraft.servers', []);
        $validSlugs = [];
        foreach ($servers as $row) {
            $name = $row['name'] ?? 'Server';
            $version = $row['version'] ?? '1.0';
            $validSlugs[] = Str::slug($name . '-' . $version);
        }

        // Удалить серверы, которых нет в конфиге (удалённые и больше не используемые)
        PrivilegeServer::whereNotIn('server_slug', $validSlugs)->delete();
        MinecraftServer::whereNotIn('slug', $validSlugs)->delete();

        $order = 0;
        foreach ($servers as $row) {
            $name = $row['name'] ?? 'Server';
            $version = $row['version'] ?? '1.0';
            $slug = Str::slug($name . '-' . $version);
            MinecraftServer::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'version' => $version,
                    'port' => (int) ($row['port'] ?? 25565),
                    'description' => null,
                    'sort_order' => $order++,
                ]
            );
        }
    }
}
