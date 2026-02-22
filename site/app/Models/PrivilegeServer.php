<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivilegeServer extends Model
{
    protected $table = 'privilege_server';

    protected $fillable = [
        'privilege_id',
        'server_slug',
    ];

    public function privilege(): BelongsTo
    {
        return $this->belongsTo(Privilege::class);
    }
}
