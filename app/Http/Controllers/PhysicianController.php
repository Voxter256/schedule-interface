<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Physician;
use Carbon\Carbon;

class PhysicianController extends Controller
{
    public function __construct(){
        $this->middleware('auth:');
    }

    public function index(){
        $physicians = Physician::all()->load('position')->sortBy(function($post) {
            $position_order = NULL;
            $position_name = $post->position->name;
            switch ($position_name) {
                case "PPP":
                    $position_order = 0;
                    break;
                case "PGY1":
                    $position_order = 1;
                    break;
                case "PGY2":
                    $position_order = 2;
                    break;
                case "PGY3":
                    $position_order = 3;
                    break;
                case "PGY4":
                    $position_order = 4;
                    break;
                case "PGY5+":
                    $position_order = 5;
                    break;
                case "Attending":
                    $position_order = 6;
                    break;
                default:
                    $position_order = $position_name;
            }
            $name_array = explode(" ", $post->name);
            $name_order = end($name_array);
            $thing = sprintf('%s %s', $position_order, $name_order);
            return $thing;
        });



        return view('physician.index', compact(['physicians']));
    }

    public function show($id){
        $today = Carbon::yesterday();

        $physician = Physician::findOrFail($id);
        $physician = $physician->load('shifts', 'shifts.service');

        $holiday_object = new \App\Libraries\UH_Holidays();
        $holidays = $holiday_object->get_list();
        foreach($holidays as $holiday){
            $carbon_holiday = Carbon::createFromTimestamp($holiday);
            // print($carbon_holiday->toDateString() . "</br>");
        }
        // print("</br>");

        $vacation_days = $physician->vacations->sortBy('start_date');
        $vacation_days_taken = 0;
        $vacation_days_available = 20;
        if (!in_array($physician->position->name, ["PGY1", "PPP"])){
            $vacation_days_available = 25;
        }
        foreach($vacation_days as $vacation){
            $this_date = $vacation->start_date;
            // print("</br>". $this_date->format('D, M j, Y') . "</br>");
            $end_date = $vacation->end_date;
            // print($end_date->format('D, M j, Y') . "</br>");
            while ($this_date->diffInDays($end_date, false) >= 0){
                if ($this_date->isWeekday() && !in_array($this_date->toDateString(), $holidays)){
                    // print($this_date->format('D, M j, Y') . "</br>");
                    $vacation_days_taken = $vacation_days_taken + 1;
                }
                $this_date->addDays(1);
            }
        }

        $vacation_days_available = $vacation_days_available - $vacation_days_taken;

        $services = $physician->shifts->where('service.is_call', 0)->where('shift_date', '>=', $today)->sortBy('shift_date')->groupBy('service_id');
        $new_services = [];
        foreach ($services as $service_key => $shifts){
            $previous_date = Carbon::minValue();
             $filtered_shifts = $shifts->each( function ($shift, $shift_key) use (&$shifts, &$previous_date, &$new_services){
                $shift_date = $shift->shift_date;
                if ($shift_key == 0) {
                    $previous_date = $shift_date;
                    $new_services[] = $shift;
                    return;
                }
                if (!$shifts->has($shift_key + 1)){
                    $new_services[] = $shift;
                    return;
                }
                $next_date = $shifts[$shift_key + 1]->shift_date;
                if ($previous_date->addDays(1)->toDateString() == $shift_date->toDateString() && $next_date->subDays(1)->toDateString() == $shift_date->toDateString()){
                    $previous_date = $shift_date;
                    return;
                } else {
                    $previous_date = $shift_date;
                    $new_services[] = $shift;
                    return;
                }
            });
        }
        $shifts = collect($new_services);
        $shifts = $shifts->sortBy('shift_date');

        return view('physician.show', compact(['physician', 'vacation_days', 'vacation_days_taken', 'shifts', 'today']));
    }
}
