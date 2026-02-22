<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Minecraft Server List Ping (1.7+ protocol).
 * @see https://wiki.vg/Server_List_Ping
 */
class MinecraftPing
{
    public function __construct(
        protected string $host,
        protected int $port,
        protected float $timeout = 2.0
    ) {}

    /**
     * Ping server and return status or null on failure.
     *
     * @return array{online: int, max: int, version?: string, latency_ms?: int}|null
     */
    public function ping(): ?array
    {
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, (int) ceil($this->timeout));
        if ($socket === false) {
            return null;
        }

        stream_set_timeout($socket, (int) ceil($this->timeout), (int) (($this->timeout - floor($this->timeout)) * 1_000_000));

        try {
            $this->sendHandshake($socket);
            $this->sendStatusRequest($socket);
            $json = $this->readStatusResponse($socket);
            if ($json === null) {
                return null;
            }
            $data = json_decode($json, true);
            if (! is_array($data)) {
                return null;
            }
            $online = (int) ($data['players']['online'] ?? 0);
            $max = (int) ($data['players']['max'] ?? 0);
            $version = isset($data['version']['name']) ? (string) $data['version']['name'] : null;
            return [
                'online' => $online,
                'max' => $max,
                'version' => $version,
            ];
        } catch (\Throwable $e) {
            Log::debug('Minecraft ping failed: ' . $e->getMessage(), [
                'host' => $this->host,
                'port' => $this->port,
            ]);
            return null;
        } finally {
            fclose($socket);
        }
    }

    private function writeVarInt($socket, int $value): void
    {
        $value = $value & 0xFFFFFFFF;
        do {
            $byte = $value & 0x7F;
            $value >>= 7;
            if ($value !== 0) {
                $byte |= 0x80;
            }
            fwrite($socket, chr($byte));
        } while ($value !== 0);
    }

    private function readVarInt($socket): ?int
    {
        $numRead = 0;
        $result = 0;
        do {
            $byte = fread($socket, 1);
            if ($byte === false || $byte === '') {
                return null;
            }
            $b = ord($byte);
            $result |= ($b & 0x7F) << (7 * $numRead);
            $numRead++;
            if ($numRead > 5) {
                return null;
            }
        } while (($b & 0x80) !== 0);
        return $result;
    }

    private function writeString($socket, string $s): void
    {
        $bytes = $s;
        $this->writeVarInt($socket, strlen($bytes));
        fwrite($socket, $bytes);
    }

    private function readString($socket): ?string
    {
        $len = $this->readVarInt($socket);
        if ($len === null || $len < 0 || $len > 65536) {
            return null;
        }
        $s = '';
        while ($len > 0) {
            $chunk = fread($socket, $len);
            if ($chunk === false || $chunk === '') {
                return null;
            }
            $s .= $chunk;
            $len -= strlen($chunk);
        }
        return $s;
    }

    private function sendHandshake($socket): void
    {
        $host = $this->host;
        $port = $this->port;
        $payload = '';
        $payload .= $this->varIntBytes(-1);
        $payload .= $this->varIntBytes(strlen($host)) . $host;
        $payload .= pack('n', $port);
        $payload .= $this->varIntBytes(1);
        $packet = $this->varIntBytes(0) . $payload;
        $packet = $this->varIntBytes(strlen($packet)) . $packet;
        fwrite($socket, $packet);
    }

    private function varIntBytes(int $value): string
    {
        $value = $value & 0xFFFFFFFF;
        $out = '';
        do {
            $byte = $value & 0x7F;
            $value >>= 7;
            if ($value !== 0) {
                $byte |= 0x80;
            }
            $out .= chr($byte);
        } while ($value !== 0);
        return $out;
    }

    private function sendStatusRequest($socket): void
    {
        $packet = $this->varIntBytes(0);
        $packet = $this->varIntBytes(strlen($packet)) . $packet;
        fwrite($socket, $packet);
    }

    private function readStatusResponse($socket): ?string
    {
        $length = $this->readVarInt($socket);
        if ($length === null || $length < 1 || $length > 65536) {
            return null;
        }
        $data = '';
        $toRead = $length;
        while ($toRead > 0) {
            $chunk = fread($socket, $toRead);
            if ($chunk === false || $chunk === '') {
                return null;
            }
            $data .= $chunk;
            $toRead -= strlen($chunk);
        }
        $offset = 0;
        if ($this->readVarIntFromString($data, $offset) === null) {
            return null;
        }
        $jsonLength = $this->readVarIntFromString($data, $offset);
        if ($jsonLength === null || $offset + $jsonLength > strlen($data)) {
            return null;
        }
        return substr($data, $offset, $jsonLength);
    }

    private function readVarIntFromString(string $data, int &$offset): ?int
    {
        $numRead = 0;
        $result = 0;
        while ($offset < strlen($data)) {
            $b = ord($data[$offset]);
            $offset++;
            $result |= ($b & 0x7F) << (7 * $numRead);
            $numRead++;
            if ($numRead > 5) {
                return null;
            }
            if (($b & 0x80) === 0) {
                return $result;
            }
        }
        return null;
    }
}
