<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BlockedDay extends Model
{
    public $timestamps = false;

    public function service(){
        return $this->belongsTo('App\Service');
    }
}
