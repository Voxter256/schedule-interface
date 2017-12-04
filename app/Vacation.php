<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vacation extends Model
{
    public $timestamps = false;

    protected $dates = ["start_date", "end_date"];

    public function physician(){
        return $this->belongsTo('App\Physician');
    }
}
