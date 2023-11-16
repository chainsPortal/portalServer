<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class transaction extends Model
{
    use HasFactory , \Awobaz\Compoships\Compoships ;

    public function pairTransaction()
    {
       return  $this->hasOne(
            transaction::class,
            ['transaction_id','interfacing_chain_id'] , ['transaction_id' , 'chain_id']
    
       );
        
    }
    public function validation()
    {
       return  $this->hasMany(
            transaction_validation::class,
            ['transaction_id','chain_id'] , ['transaction_id' , 'chain_id']
    
       );
        
    }
}
