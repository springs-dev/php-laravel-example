<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', 'API\AuthController@login');
Route::post('/reset-password', 'API\AuthController@resetPassword');
Route::post('/change-password', 'API\AuthController@changePassword');
Route::post('/set-password/{token}', 'API\AuthController@setPassword');




Route::middleware('auth:api')->group(function () {
    Route::post('/logout', 'API\AuthController@logout');
    Route::resource('/trainings', 'API\TrainingsController');
    Route::resource('/invoice-templates', 'API\InvoiceTemplatesController');

    /*
     * Trainings
     * */
    Route::put('/trainings/{id}/change-date', 'API\TrainingsController@changeDate');
    Route::get('/trainings/{id}/to-execute', 'API\TrainingsController@moveToExecute');
    Route::get('/trainings/{id}/postponed', 'API\TrainingsController@postponed');
    Route::get('/trainings/{id}/non-active', 'API\TrainingsController@nonActive');
    Route::get('/trainings/{employee_id}/planning-overview', 'API\TrainingsController@planningOverview');
    Route::get('/responsive-users', 'API\TrainingsController@responsiveUsers');

    /*
     * Invoice Templates
     * */
    Route::get('/expense-invoice-templates/', 'API\InvoiceTemplatesController@showExpense');
    Route::get('/revenue-invoice-templates/', 'API\InvoiceTemplatesController@showRevenue');
    Route::post('/add-file/', 'API\InvoiceTemplatesController@addFile');
    Route::get('/show-pdf/{invoice_template_id}/{invoice_id}', 'API\InvoiceTemplatesController@showPDF');

});
