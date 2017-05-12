<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


Route::any('/', 'AuthController@index');

Route::any('/auth/check', 'AuthController@check');
Route::any('/auth/login', 'AuthController@login');
Route::any('/auth/logout', 'AuthController@logout');

Route::group(['namespace' => 'Admin', 'middleware' => 'auth'], function() {

    Route::get('/admin/list', 'AdminController@list');
    Route::post('/admin/add', 'AdminController@add');
    Route::post('/admin/edit', 'AdminController@edit');
    Route::post('/admin/status', 'AdminController@status');
    Route::post('/admin/delete', 'AdminController@delete');

});
