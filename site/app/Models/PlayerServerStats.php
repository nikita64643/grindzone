<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerServerStats extends Model
{
    protected $fillable = [
        'user_id',
        'server_slug',
        'playtime_minutes',
        'mob_kills',
        'votes',
        'silver',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'playtime_minutes' => 'integer',
            'mob_kills' => 'integer',
            'votes' => 'integer',
            'silver' => 'decimal:2',
            'synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
