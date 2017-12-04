<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $physician = Auth::user()->physician;
        if (is_null($physician)){
            $id = 5;
        } else {
            $id = $physician->id;
        }
        return redirect('physicians/'.$id);
    }
}
