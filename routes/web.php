<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('vacation_planner', 'PlannerController@check_vacation');
Route::post('vacation_planner', 'PlannerController@results');

Route::get('call_switch/{call_id}', 'PlannerController@call_results');

Route::get('physicians', 'PhysicianController@index');
Route::get('physicians/{id}', 'PhysicianController@show');

Route::get('shifts', 'ShiftController@show_today');
Route::post('shifts', 'ShiftController@show_day');

Auth::routes();
Route::get('register/verify/{token}','Auth\RegisterController@verify');

Route::get('/home', 'HomeController@index')->name('home');
