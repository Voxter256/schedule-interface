<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    public $timestamps = false;

    protected $dates = ["shift_date"];

    // protected $dateFormat = 'Y-m-d H:i:s';

    public function service(){
        return $this->belongsTo('App\Service');
    }

    public function physician(){
        return $this->belongsTo('App\Physician');
    }

    public function day_shift(){
        $this_shift = Shift::whereDate('shift_date',  $this->shift_date)
        ->where('physician_id', $this->physician_id)
        ->whereHas('service', function ($query){
            $query->where('is_call', False);
        })->first();
        return $this_shift;
    }
}
