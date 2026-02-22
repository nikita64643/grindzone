<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MinecraftServer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Inertia\Inertia;
use Inertia\Response;

class ServerController extends Controller
{
    public function index(): Response
    {
        $servers = MinecraftServer::query()
            ->orderBy('version')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $list = $servers->map(fn(MinecraftServer $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'slug' => $s->slug,
            'version' => $s->version,
            'port' => $s->port,
            'description' => $s->description,
            'log_path' => $this->serverLogPath($s),
            'has_log' => File::exists($this->serverLogPath($s)),
        ]);

        return Inertia::render('admin/servers/Index', [
            'servers' => $list,
        ]);
    }

    public function show(MinecraftServer $server): Response
    {
        $logPath = $this->serverLogPath($server);
        $logExists = File::exists($logPath);

        return Inertia::render('admin/servers/Show', [
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'slug' => $server->slug,
                'version' => $server->version,
                'port' => $server->port,
                'description' => $server->description,
                'log_path' => $logPath,
                'has_log' => $logExists,
                'easydonate_server_id' => (int) $server->easydonate_server_id,
            ],
        ]);
    }

    public function log(MinecraftServer $server, Request $request)
    {
        $path = $this->serverLogPath($server);
        if (! File::exists($path)) {
            return response()->json(['content' => '', 'error' => 'Файл лога не найден.'], 404);
        }

        $content = File::get($path);
        $lines = $request->integer('lines', 500);
        if ($lines > 0) {
            $allLines = explode("\n", $content);
            $tail = array_slice($allLines, -$lines);
            $content = implode("\n", $tail);
        }

        return response()->json(['content' => $content]);
    }

    public function update(Request $request, MinecraftServer $server): RedirectResponse
    {
        $request->validate([
            'easydonate_server_id' => ['nullable', 'integer', 'min:0'],
        ]);
        $server->update([
            'easydonate_server_id' => (int) ($request->easydonate_server_id ?? 0),
        ]);
        return back()->with('status', 'Сервер обновлён.');
    }

    public function restart(MinecraftServer $server)
    {
        $port = $server->port;
        $basePath = config('minecraft.servers_path', base_path('../servers'));
        $versionFolders = config('minecraft.version_folders', []);
        $versionFolder = $versionFolders[$server->version] ?? preg_replace('/\s.*$/', '', $server->version);
        $serverDir = $basePath . DIRECTORY_SEPARATOR . $versionFolder . DIRECTORY_SEPARATOR . $server->name;

        $scriptsDir = dirname($basePath) . DIRECTORY_SEPARATOR . 'scripts';
        $restartScript = $scriptsDir . DIRECTORY_SEPARATOR . 'restart-server.js';

        if (! File::exists($restartScript)) {
            return back()->withErrors(['restart' => 'restart-server.js не найден: ' . $restartScript]);
        }

        $result = Process::timeout(45)->run([
            'node',
            $restartScript,
            '--port',
            (string) $port,
            '--version',
            $versionFolder,
            '--name',
            $server->name,
        ]);

        if (! $result->successful()) {
            $err = trim($result->errorOutput() ?: $result->output());
            return back()->withErrors(['restart' => 'Ошибка перезапуска: ' . ($err ?: 'код ' . $result->exitCode())]);
        }

        return back()->with('status', 'Сервер перезапущен. Подождите 15–30 сек.');
    }

    private function serverLogPath(MinecraftServer $server): string
    {
        $base = config('minecraft.servers_path', base_path('../servers'));
        $versionFolders = config('minecraft.version_folders', []);
        $versionFolder = $versionFolders[$server->version] ?? preg_replace('/\s.*$/', '', $server->version);

        return $base . DIRECTORY_SEPARATOR . $versionFolder . DIRECTORY_SEPARATOR . $server->name . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'latest.log';
    }
}
