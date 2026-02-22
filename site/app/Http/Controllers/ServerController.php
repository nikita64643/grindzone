<?php

namespace App\Http\Controllers;

use App\Models\MinecraftServer;
use App\Http\Controllers\Api\ServerStatusController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServerController extends Controller
{
    /**
     * Show all servers grouped by game.
     */
    public function index(): Response
    {
        $servers = MinecraftServer::query()
            ->orderBy('version')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $cached = ServerStatusController::getCachedStatus();

        $minecraftServers = $servers->map(fn ($s) => [
            'name' => $s->name,
            'slug' => $s->slug,
            'version' => $s->version,
            'port' => $s->port,
            'status' => $cached[$s->port] ?? null,
        ])->values()->all();

        $groups = [
            [
                'game' => 'Minecraft',
                'available' => true,
                'servers' => $minecraftServers,
            ],
            [
                'game' => 'CS2',
                'available' => false,
                'servers' => [],
            ],
            [
                'game' => 'CS1.6',
                'available' => false,
                'servers' => [],
            ],
            [
                'game' => 'CS:Source',
                'available' => false,
                'servers' => [],
            ],
            [
                'game' => 'CSv34',
                'available' => false,
                'servers' => [],
            ],
            [
                'game' => 'DayZ',
                'available' => false,
                'servers' => [],
            ],
        ];

        return Inertia::render('servers/Index', [
            'groups' => $groups,
        ]);
    }

    /**
     * Show a single Minecraft server (mode) page.
     */
    public function show(Request $request, MinecraftServer $server): Response
    {
        $status = null;
        $cached = ServerStatusController::getCachedStatus();
        if (isset($cached[$server->port])) {
            $status = $cached[$server->port];
        }

        $mods = collect(config('minecraft.mods_by_version', []))->get($server->version, []);

        return Inertia::render('servers/Show', [
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'slug' => $server->slug,
                'version' => $server->version,
                'port' => $server->port,
                'description' => $server->description,
            ],
            'status' => $status,
            'mods' => array_values($mods),
        ]);
    }
}
