<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    public $timestamps = false;

    protected $dates = ["shift_date"];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public function service(){
        return $this->belongsTo('App\Service');
    }

    public function physician(){
        return $this->belongsTo('App\Physician');
    }
}
