<?php

use Illuminate\Support\Facades\Route;

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


Route::get('/', function (){
    return view('welcome');
});

Route::post('/make-auth', 'Controller@makeAuth');
Route::get('/refresh-token', 'Controller@oauthGetRefreshToken');

Route::post('/make-deal-and-task', 'Controller@joinDealAndTask');
