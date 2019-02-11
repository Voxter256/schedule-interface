<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScheduleUpdate extends Model
{
    public $timestamps = false;

    protected $dates = ["update_date"];
}
