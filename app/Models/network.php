<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class network extends Model
{
    use HasFactory;

    public function supportedChains()
    {
        return $this->hasMany(network_supported_chain::class, 'chain_id', 'chain_id');
    }
}
