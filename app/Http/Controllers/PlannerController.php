<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;

use App\Physician;
use App\Position;
use App\Service;
use App\Shift;
use App\Vacation;

class PlannerController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }

    public function check_vacation(){
        $physicians = Physician::orderBy('name', 'asc')->get();
        $user = Auth::user()->physician;
        if (is_null($user)){
            $user_id = 5;
        } else {
            $user_id = $user->id;
        }

        return view('planner.check', compact(['physicians', 'user_id']));
    }

    public function results(Request $request){
        $physician_id = $request->input('physician');
        $user = Physician::find($physician_id);
        $start_date = new Carbon($request->input('start_date'));
        $end_date = new Carbon($request->input('end_date'));
        // $start_date = Carbon::createFromFormat("m/d/Y", "11/06/2017");
        // $end_date = Carbon::createFromFormat("m/d/Y", "11/08/2017");
        list ($success, $messages, $call_array) = $this->optimize_vacation($user, $start_date, $end_date);
        if ($success === False){
            $call_array = Null;
        }
        return view('planner.results', compact(['start_date', 'end_date', 'success', 'messages', 'call_array']));
    }

    private function optimize_vacation($user, $start_date, $end_date){
        $all_potential_calls = [];
        $messages = [];

        $date_delta = $end_date->diffInDays($start_date);
        $vacation_date_list = [];
        for ($i = 0;  $i <= $date_delta; $i ++){
            $start_date_clone = clone($start_date);
            $vacation_date_list[] = $start_date->addDays($i)->toDateString();
            $start_date = $start_date_clone;
        }
        // var_dump($vacation_date_list);
        $vacation_time_valid = $this->able_to_take_vacation($user, $vacation_date_list, $all_potential_calls, $messages);

        if ($vacation_time_valid !== True){
            return array ($vacation_time_valid, $messages, $all_potential_calls);
        }
        // print("Vacation Time Valid</br>");

        $messages[] = [
            'type' => 'header',
            'size' => 'large',
            'message' => 'Check for calls you need to switch'
        ];

        $current_call_days = $this->find_current_call_days($user, $vacation_date_list);

        $current_call_days_count = $current_call_days->count();
        // print("Have " . $current_call_days_count . " current call days</br>");
        if ($current_call_days_count == 0){
            // print("No calls to switch, you're good to go!");
            $messages[] = [
                'type' => 'info',
                'message' => 'OK'
            ];
            return array (True, $messages, $all_potential_calls);
        }

        $excluded_physicians = [];
        foreach ($all_potential_calls as $this_potential){
            $this_physician = $this_potential["original"]->physician->id;
            if (!in_array($this_physician, $excluded_physicians)){
                $excluded_physicians[] = $this_physician;
            }
        }

        $potential_call_switch = $this->get_potential_call_switch($user, $current_call_days, $all_potential_calls, $messages, $excluded_physicians, $vacation_date_list);


        if ($potential_call_switch == False){
            return array (False, $messages, $all_potential_calls);
        }
        // print(count($potential_call_switch) . "</br>");
        // foreach ($potential_call_switch as $this_shift){
             // print($this_shift->shift_date->toDateString()  . " " . $this_shift->physician->name . " " . $this_shift->id . "</br>");
        // }

        return array (True, $messages, $all_potential_calls);
    }

    private function able_to_take_vacation($user, $vacation_date_list, &$all_potential_calls, &$messages){

        $user_position_name = $user->position->name;

        // PGY1 can switch with PPP
        if (in_array($user_position_name, ["PPP", "PGY1"])){
            $user_position_id_array = [];
            $positions = Position::whereIn('name', ["PPP", "PGY1"])->get();
            foreach($positions as $position){
                $user_position_id_array[] = $position->id;
            }
        } else {
            $user_position_id_array = [$user_position_name];
        }




        $vacation_shifts = Shift::with('service')
        ->whereRaw("\"shifts\".\"shift_date\"::date IN ('" . implode("'::date, '", $vacation_date_list) . "'::date)")
        ->where('physician_id', '=', $user->id)
        ->whereHas('service', function ($query) {
            $query->where('is_call', '=', '0');
        })->get();

        foreach($vacation_shifts as $this_shift){

            # Can't be on IM (9), addiction (6), child and adolescent psych (7)
            # service->vacation_allowed must be True
            if ($this_shift->service->vacation_allowed == 0){
                $errorString = "Can't take vacation on " . $this_shift->service->name;
                // print($errorString . "</br>");
                $messages[] = [
                    'type' => 'danger',
                    'message' => $errorString
                ];
                return False;
            }

            # check if its a weekday | Saturday: 6, Sunday: 0
            if (in_array($this_shift->shift_date->dayOfWeek, [0,6])){
                continue;
            }

            $messages[] = [
                'type' => 'header',
                'size' => 'large',
                'message' => 'Physician count on ' . $this_shift->shift_date->format('D, M j, Y')
            ];

            # Rule 2)
            # Must be at least 1 resident on certain services, 2 for Consult-Liaison
            $other_physicians = Physician::whereIn('position_id', $user_position_id_array)->where('id', '!=', $user->id)
            ->whereHas('shifts', function ($query) use (&$this_shift){
                $query->where('service_id', '=', $this_shift->service_id)->whereDate('shift_date', '=', $this_shift->shift_date->toDateString());
            })->whereDoesntHave('vacations', function ($query) use (&$this_shift){
                $query->whereDate('start_date', '<=', $this_shift->shift_date->toDateString())->whereDate('end_date', '>=', $this_shift->shift_date->toDateString());
            })->get();

            if ($other_physicians->count() < $this_shift->service->required_number_residents){
                $plural_string = ($other_physicians->count() != 1) ? "s" : "";
                $errorString = "On " . $this_shift->shift_date->toDateString() . " there are " . $other_physicians->count()  . " resident" . $plural_string . " on " . $this_shift->service->name .  " when it requires at least " . $this_shift->service->required_number_residents;
                $messages[] = [
                    'type' => 'danger',
                    'message' => $errorString
                ];

                $physicians_on_shift = Physician::whereIn('position_id', $user_position_id_array)->where('id', '!=', $user->id)
                ->whereHas('shifts', function ($query) use (&$this_shift){
                    $query->where('service_id', '=', $this_shift->service_id)->whereDate('shift_date', '=', $this_shift->shift_date->toDateString());
                })->get();

                if ($physicians_on_shift->count() == 0){
                    $messages[] = [
                        'type' => 'info',
                        'message' => 'There are no other physicians in your year that work this shift'
                    ];
                } else {
                    foreach ($physicians_on_shift as $this_physician){
                            $vacation = Vacation::where('physician_id', $this_physician->id)->whereDate('start_date', '<=', $this_shift->shift_date->toDateString())->whereDate('end_date', '>=', $this_shift->shift_date->toDateString())->first();
                            $info_message = $this_physician->name . " has vacation from " . $vacation->start_date->format('D, M j, Y') . " to " . $vacation->end_date->format('D, M j, Y');
                            $messages[] = [
                                'type' => 'danger',
                                'message' => $info_message
                            ];
                    }
                }


                return False;
            } else if ($other_physicians->count() == $this_shift->service->required_number_residents){
                // print("Check for post call conflicts on " . $this_shift->shift_date->toDateString() . "</br>");
                foreach ($other_physicians as $this_other_physician){
                    // print($this_other_physician->name . ": ");
                    $post_call_day = Shift::where('physician_id', $this_other_physician->id)
                    ->whereDate('shift_date', '=', $this_shift->shift_date->subDays(1)->toDateString())
                    ->whereHas('service', function ($query){
                        $query->where('is_call', '=', '1');
                    })->get();

                    // print($post_call_day->count() . "</br>");
                    if ($post_call_day->count() >= 1){
                        $potential_call_switch = $this->get_potential_call_switch($this_other_physician, $post_call_day, $all_potential_calls, $messages, [$user->id]);
                        // if (count($potential_call_switch) > 0){
                        //     foreach ($potential_call_switch as $this_potential_call_switch){
                        //         // print($this_potential_call_switch->shift_date->toDateString()  . " " . $this_potential_call_switch->physician->name . " " . $this_potential_call_switch->id . "</br>");
                        //     }
                        // }
                    } else {
                        $messages[] = [
                            'type' => 'info',
                            'message' => 'OK'
                        ];
                    }
                }
            } else {
                $messages[] = [
                    'type' => 'info',
                    'message' => 'OK'
                ];
            }

        }
        return True;
    }

    private function find_current_call_days($user, $vacation_date_list){

        # Determine what call days need switched
        $results = DB::table('shifts')
            ->select('shifts.id')
            ->join('services', 'shifts.service_id', '=', 'services.id')
            ->where('shifts.physician_id', '=', $user->id)
            ->where('services.is_call', '=', '1')
            ->whereRaw("\"shifts\".\"shift_date\"::date IN ('" . implode("'::date, '", $vacation_date_list) . "'::date)")->get();

            $shift_days = [];
        foreach ($results as $result){
            $shift_days[] = $result->id;
        }

        $call_days = Shift::whereIn('id', $shift_days)->get();
        return $call_days;
    }

    private function get_potential_call_switch($user, $current_call_days, &$all_potential_calls, &$messages, $not_these_physicians = [], $vacation_date_list){
        # TODO remove call days from vacation date list

        $user_position_name = $user->position->name;

        // PGY1 can switch with PPP
        if (in_array($user_position_name, ["PPP", "PGY1"])){
            $user_position_id_array = [];
            $positions = Position::whereIn('name', ["PPP", "PGY1"])->get();
            foreach($positions as $position){
                $user_position_id_array[] = $position->id;
            }
        } else {
            $user_position_id_array = [$user_position_name];
        }

        $potential_shifts = [];
        foreach ($current_call_days as $call_day){
            // print("Call on " . $call_day->shift_date->toDateString() . "</br>");

            # -- Find physicians who can take your call -- #

            $messages[] = [
                'type' => 'header',
                'size' => 'small',
                'message' => $user->name . '\'s call on ' . $call_day->shift_date->format('D, M j, Y')
            ];


            $physicians_on_service_without_call = Physician::with('shifts')
            ->where('id', '!=', $user->id)->whereIn('position_id', $user_position_id_array)
            ->whereNotIn('id', $not_these_physicians)
            ->whereHas('shifts', function ($query) use (&$call_day){
                $query->whereDate('shift_date', '=', $call_day->shift_date->toDateString())
                ->with('service')->whereHas('service', function ($newQuery){
                    $newQuery->where('has_call', '=', Null);
                });
            })->get();

            foreach($physicians_on_service_without_call as $this_physician){
                $not_these_physicians[] = $this_physician->id;
                $messages[] = [
                    'type' => 'info',
                    'message' => $this_physician->name . ' is on a service without call'
                ];
            }

            $physicians_on_vacation = Physician::with('shifts')
            ->where('id', '!=', $user->id)->whereIn('position_id', $user_position_id_array)
            ->whereNotIn('id', $not_these_physicians)
            ->whereHas('vacations', function ($query) use (&$call_day){  // Can't be on vacation
                $query->whereDate('start_date', '<=', $call_day->shift_date->toDateString())->whereDate('end_date', '>=', $call_day->shift_date->toDateString());
            })->get();

            foreach($physicians_on_vacation as $this_physician){
                $not_these_physicians[] = $this_physician->id;
                $messages[] = [
                    'type' => 'info',
                    'message' => $this_physician->name . ' is on vacation'
                ];
            }


            # Find physicians with your title and on a service with call on this call day
            $available_physicians = Physician::with('shifts')
            ->where('id', '!=', $user->id)->whereIn('position_id', $user_position_id_array)
            ->whereNotIn('id', $not_these_physicians)
            ->get();
            // print($available_physicians->count() . "</br>");

            # Optimal query but doesn't identify why each physician is removed
            // $available_physicians = Physician::with('shifts')
            // ->where('id', '!=', $user->id)->where('position_id', '=', $user->position_id)
            // ->whereNotIn('id', $not_these_physicians)
            // ->whereHas('shifts', function ($query) use (&$call_day){
            //     $query->whereDate('shift_date', '=', $call_day->shift_date->toDateString())
            //     ->with('service')->whereHas('service', function ($newQuery){
            //         $newQuery->where('has_call', '=', '1');
            //     });
            // })->whereDoesntHave('vacations', function ($query) use (&$call_day){  // Can't be on vacation
            //     $query->whereDate('start_date', '<=', $call_day->shift_date->toDateString())->whereDate('end_date', '>=', $call_day->shift_date->toDateString());
            // })->get();


            # get their call days the week of this call
            $potential_individual_shifts = []; // if a physician has call that week, only that call can be switched
            $available_physicians = $this->remove_physicians_breaking_call_rules($available_physicians, $call_day, $messages, $potential_individual_shifts);
            // print("Final count of available physicians: " . (string) count($available_physicians) . "</br>");

            // can't be one of the requested vacation addDays
            $potential_individual_shifts = collect($potential_individual_shifts)->filter(function ($value, $key) use ($vacation_date_list, &$messages){
                $result = !in_array($value->shift_date->toDateString(), $vacation_date_list);

                if (!$result){
                    $messages[] = [
                        'type' => 'info',
                        'message' => $value->physician->name . '\'s call on '. $value->shift_date->format('D, M j, Y') . ' is during your requested vacation'
                    ];
                }

                return $result;
            });

            if (count($available_physicians) == 0){
                // print("Vacation not possible!");
                $messages[] = [
                    'type' => 'danger',
                    'message' => 'No Physicians available to switch your call on ' . $call_day->shift_date->format('D, M j, Y')
                ];
                return False;
            }
            $available_physicians_final = [];
            foreach ($available_physicians as $this_physician){
                $available_physicians_final[] = $this_physician->id;
            }
            # -- Find which calls you can take of available physicians -- #

            # must be on a service that has call to take it
            $potential_days = Shift::where('physician_id', '=', $user->id)->whereDate('shift_date', '>=', Carbon::now()->toDateString())
            ->whereHas('service', function ($query){
                $query->where('has_call', '=', '1');
            })->get();

            // print($potential_days->count() . "</br>");

            $potential_days_2 = [];
            foreach ($potential_days as $x){
                $potential_days_2[] = $x->shift_date->toDateString();
            }

            $potential_shifts = Shift::with('physician')
            ->whereIn('physician_id', $available_physicians_final)
            ->whereRaw("\"shifts\".\"shift_date\"::date IN ('" . implode("'::date, '", $potential_days_2) . "'::date)")
            ->whereHas('service', function ($query){
                $query->where('is_call', '=', '1');
            })->get();

            # check call days
            $potential_shifts = $this->remove_shifts_breaking_call_rules($potential_shifts, $user);

            // join $potential_individual_shifts with potential shifts
            $potential_shifts = $potential_shifts->concat($potential_individual_shifts);
            $potential_shifts = $potential_shifts->sortBy('shift_date');

            # can't be on vacation
            $potential_shifts = $this->remove_shifts_user_on_vacation($user, $potential_shifts, $messages);
            // print(count($potential_shifts) . "</br>");

            if ($potential_shifts->count() > 0){
                $all_potential_calls[] = [
                    'original' => $call_day,
                    'potentials' => $potential_shifts
                ];
            }
        }
        return $potential_shifts;
    }

    private function get_zero_days($call_day){
        # get their call days that week
        $this_shift_date = $call_day->shift_date;
        $this_week_day = $this_shift_date->dayOfWeek;
        $zero_days_dictionary = array(
            # numbers are relative to indexed day
            0 => [-1, 0, 1, 2, 3, 4],  # Sun: not Sat, Sun, next -> Mon, Tue, Wed, Thr
            1 => [-1, 0, 1, 2, 3],  # Mon: not Sun, Mon, Tue, Wed, Thr
            2 => [-2, -1, 0, 1, 2],  # Tue: not Sun, Mon, Tue, Wed, Thr
            3 => [-3, -2, -1, 0, 1],  # Wed: not Sun, Mon, Tue, Wed, Thr
            4 => [-4, -3, -2, -1, 0, 1],  # Thr: not Sun, Mon, Tue, Wed, Thr, Fri
            5 => [-1, 0, 1],  # Fri: not Thr, Fri, Sat
            6 => [-1, 0, 1],  # Sat: not Fri, Sat, Sun
        );
        $zero_days = [];
        foreach ($zero_days_dictionary[$this_week_day] as $x){
            $this_shift_date_clone = clone($this_shift_date);
            $zero_days[] = $this_shift_date_clone->addDays($x)->toDateString();
        }

        return $zero_days;
    }

    private function remove_physicians_breaking_call_rules($available_physicians, $call_day, &$messages, &$potential_individual_shifts){
        $new_available_physicians = [];
        $zero_days = $this->get_zero_days($call_day);

        foreach ($available_physicians as $physician){

            # can't have a second post-call day
            $call_days_to_check = Shift::where('physician_id', '=', $physician->id)
            ->whereRaw("\"shifts\".\"shift_date\"::date IN ('" . implode("'::date, '", $zero_days) . "'::date)")
            ->whereHas('service', function ($query){
                $query->where('is_call', '=', '1');
            })->orderBy('shift_date', 'asc')
            ->get();

            if ($call_days_to_check->count() >= 1){
                // print($physician->name . '</br>');
                // print("Too many calls</br>");
                foreach ($call_days_to_check as $day){

                    // print("Currently has call on: " . $day->shift_date->toDateString() . "</br>");
                    $messages[] = [
                        'type' => 'info',
                        'message' => $physician->name . ' already has call on '. $day->shift_date->format('D, M j, Y') . ', so you can only switch that shift'
                    ];
                    $potential_individual_shifts[] = $day;
                }
                // print("</br>");
                continue;
            }
            $new_available_physicians[] = $physician;
        }
        # TODO Must not be within two days of starting/ending medicine
        return $new_available_physicians;
    }

    private function remove_shifts_breaking_call_rules($potential_shifts, $user){
        # can't have a second post-call day

        $zero_days_array = [];
        foreach ($potential_shifts as $shift){
            $zero_days = $this->get_zero_days($shift);
            $zero_days_array[$shift->shift_date->toDateString()] = $zero_days;
        }

        $zero_days = [];
        foreach ($zero_days_array as $key => $call_day){
            foreach ($call_day as $key2 => $item){
                if (!in_array($item, $zero_days)){
                    $zero_days[] = $item;
                }
            }
        }

        $call_days_to_check = Shift::where('physician_id', '=', $user->id)
        ->whereRaw("\"shifts\".\"shift_date\"::date IN ('" . implode("'::date, '", $zero_days) . "'::date)")
        ->whereHas('service', function ($query){
            $query->where('is_call', '=', '1');
        })->orderBy('shift_date', 'asc')
        ->get();

        // print("Number of call days to check: " . $call_days_to_check->count() . "</br>");

        $amended_potential_shifts = [];
        foreach ($potential_shifts as $shift){
            $shift_date = $shift->shift_date->toDateString();
            $is_zero_day = False;
            foreach ($call_days_to_check as $call_day){
                if (in_array($call_day->shift_date->toDateString(), $zero_days_array[$shift_date])){
                    $is_zero_day = True;
                }
            }
            if (!$is_zero_day){
                $amended_potential_shifts[] = $shift;
            }
        }

        # TODO Must not be within two days of starting/ending medicine

        return collect($amended_potential_shifts)->sortBy('shift_date');
    }

    private function remove_shifts_user_on_vacation($user, $potential_shifts, &$messages){
        $vacations = Vacation::where('physician_id', '=', $user->id)->get();
        $amended_potential_shifts = [];

        foreach ($potential_shifts as $shift){
            $on_vacation = False;
            foreach ($vacations as $vacation){
                if ($vacation->start_date <= $shift->shift_date && $shift->shift_date <= $vacation->end_date){
                    // print("On Vacation");
                    $message_text = 'You cannot swap with ' . $shift->physician->name .  ' on ' . $shift->shift_date->format('D, M j, Y') . ' because you are on vacation';
                    $messages[] = [
                        'type' => 'info',
                        'message' => $message_text
                    ];
                    $on_vacation = True;
                    break;
                }
            }
            if (!$on_vacation){
                $amended_potential_shifts[] = $shift;
            }
        }
        return collect($amended_potential_shifts);
    }
}
