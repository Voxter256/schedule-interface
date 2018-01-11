<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use App\Physician;
use App\Shift;

class ShiftController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }

    public function show_day(Request $request){
        $date = new Carbon($request->input('date'));
        return $this->day_shifts($date);
    }

    public function show_today(){
        $date = Carbon::today();
        return $this->day_shifts($date);
    }

    public function day_shifts(Carbon $date){
        $previous_date = $date->copy()->subDays(1);
        $physicians_on_vacation = Physician::whereHas('vacations', function ($query) use (&$date){
            $query->whereDate('start_date', '<=', $date->toDateString())->whereDate('end_date', '>=', $date->toDateString());
        })->get();
        $physicians_on_vacation_ids = [];
        foreach ($physicians_on_vacation as $physician){
            $physicians_on_vacation_ids[] = $physician->id;
        }

        $physicians_post_call = Physician::where('position_id', '<>', 1)->whereHas('shifts', function ($query) use (&$previous_date){
            $query->join('services', 'shifts.service_id', 'services.id')->whereDate('shift_date', $previous_date->toDateString())->where('services.is_call', 1);
        })->get();
        $physicians_post_call_ids = [];
        foreach ($physicians_post_call as $physician){
            $physicians_post_call_ids[] = $physician->id;
        }

        $day_shifts = [];
        $call_shifts = [];

        $shifts = Shift::whereDate('shift_date', $date)->
        whereNotIn('physician_id', $physicians_on_vacation_ids)->
        whereNotIn('physician_id', $physicians_post_call_ids)->
        get();
        $shifts = $shifts->load('physician', 'service')->sortBy(function($post) {
                    $name_array = explode(" ", $post->physician->name);
                    $name_order = end($name_array);
                    $service_order = $post->service->name;
                    $thing = sprintf('%s %s', $service_order, $name_order);
                    return $thing;

                });
        foreach ($shifts as $shift) {
            if ($shift->service->is_call == 1){
                $call_shifts[] = $shift;
            }else{
                $day_shifts[] = $shift;
            }
        }

        return view('shift.show_day', compact(['date', 'day_shifts', 'call_shifts', 'physicians_post_call', 'physicians_on_vacation']));
    }

}
