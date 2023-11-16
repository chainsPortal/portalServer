<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class asset extends Model
{
    use HasFactory , \Awobaz\Compoships\Compoships;
    public function supportedChains()
    {
        
        return  $this->hasMany(
            asset_supported_chain::class,
            ['asset_address','chain_id'] , ['asset_address' , 'chain_id']
    
       );
    }
}
