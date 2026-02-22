<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Privilege extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'price',
        'features',
        'lp_permissions',
        'easydonate_product_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'lp_permissions' => 'array',
            'easydonate_product_id' => 'integer',
        ];
    }

    public function privilegeServers(): HasMany
    {
        return $this->hasMany(PrivilegeServer::class);
    }

    public function getServerSlugs(): array
    {
        return $this->privilegeServers()->pluck('server_slug')->all();
    }
}
