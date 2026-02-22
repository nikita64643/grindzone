<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinecraftServer extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'version',
        'port',
        'description',
        'sort_order',
        'easydonate_server_id',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'sort_order' => 'integer',
            'easydonate_server_id' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
