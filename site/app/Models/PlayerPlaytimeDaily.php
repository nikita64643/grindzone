<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerPlaytimeDaily extends Model
{
    protected $table = 'player_playtime_daily';

    protected $fillable = [
        'user_id',
        'date',
        'minutes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
