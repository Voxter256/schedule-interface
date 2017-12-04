<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public $timestamps = false;

    public function blocked_days(){
        return $this->hasMany('App\BlockedDay');
    }

    public function shifts(){
        return $this->hasMany('App\Shift');
    }
}
