<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Physician extends Model
{
    public $timestamps = false;

    public function position(){
        return $this->belongsTo('App\Position');
    }

    public function shifts(){
        return $this->hasMany('App\Shift');
    }

    public function user(){
        return $this->hasOne('App\User', 'email', 'email');
    }

    public function vacations(){
        return $this->hasMany('App\Vacation');
    }

}
