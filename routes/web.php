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

Route::get('physicians', 'PhysicianController@index');
Route::get('physicians/{id}', 'PhysicianController@show');

Auth::routes();
Route::get('register/verify/{token}','Auth\RegisterController@verify');

Route::get('/home', 'HomeController@index')->name('home');
