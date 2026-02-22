<?php

namespace Database\Seeders;

use App\Models\MinecraftServer;
use App\Models\Privilege;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PrivilegeSeeder extends Seeder
{
    public function run(): void
    {
        $privileges = config('donate.privileges', []);
        $serverSlugs = $this->getAllServerSlugs();

        foreach ($privileges as $key => $data) {
            $p = Privilege::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $data['name'] ?? $key,
                    'description' => $data['description'] ?? '',
                    'price' => $data['price'] ?? 0,
                    'features' => $data['features'] ?? [],
                    'lp_permissions' => $data['lp_permissions'] ?? [],
                ]
            );

            $p->privilegeServers()->delete();
            foreach ($serverSlugs as $slug) {
                $p->privilegeServers()->create(['server_slug' => $slug]);
            }
        }
    }

    /**
     * Список slug всех серверов (1.16.5, 1.21.10). Всё из БД.
     */
    private function getAllServerSlugs(): array
    {
        $slugs = [];
        $fromDb = MinecraftServer::orderBy('version')->orderBy('sort_order')->get(['slug']);
        foreach ($fromDb as $s) {
            if ($s->slug) {
                $slugs[$s->slug] = true;
            }
        }
        foreach (config('minecraft.servers', []) as $s) {
            $slug = Str::slug(($s['name'] ?? 'Server') . '-' . ($s['version'] ?? '1.0'));
            $slugs[$slug] = true;
        }
        return array_keys($slugs);
    }
}
