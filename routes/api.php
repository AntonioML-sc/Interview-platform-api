<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// presentation
Route::get('/', function() { return "api root"; });

// authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group(["middleware" => "jwt.auth"] , function() {
    Route::get('/my-profile', [AuthController::class, 'myProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/my-profile/update', [AuthController::class, 'updateMyProfile']);
    Route::put('/my-profile/delete', [AuthController::class, 'deleteMyAccount']);
});

// company routes

Route::get('/companies/get-all', [CompanyController::class, 'getAll']);
Route::get('/companies/get-by-name/{name}', [CompanyController::class, 'getByName']);

Route::group(["middleware" => ["jwt.auth", "isRecruiter"]], function() {
    Route::post('/companies/new', [CompanyController::class, 'newCompany']);
});