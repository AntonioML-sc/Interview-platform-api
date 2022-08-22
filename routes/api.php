<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\TestController;
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
Route::get('/', function () {
    return "api root";
});

// authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group(["middleware" => "jwt.auth"], function () {
    Route::get('/my-profile', [AuthController::class, 'myProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/my-profile/update', [AuthController::class, 'updateMyProfile']);
    Route::put('/my-profile/delete', [AuthController::class, 'deleteMyAccount']);
});

// company routes
Route::get('/companies/get-all', [CompanyController::class, 'getAll']);
Route::get('/companies/get-by-name/{name}', [CompanyController::class, 'getByName']);

Route::group(["middleware" => ["jwt.auth", "isRecruiter"]], function () {
    Route::post('/companies/new', [CompanyController::class, 'newCompany']);
    Route::put('/companies/update/{companyId}', [CompanyController::class, 'updateCompany']);
});

// skill routes
Route::get('/skills/get-all', [SkillController::class, 'getAll']);
Route::get('/skills/get-by-title/{title}', [SkillController::class, 'getByTitle']);

Route::group(["middleware" => ["jwt.auth", "isRecruiter"]], function () {
    Route::post('/skills/new', [SkillController::class, 'newSkill']);
    Route::put('/skills/update/{skillId}', [SkillController::class, 'updateSkill']);
    Route::delete('/skills/delete/{skillId}', [SkillController::class, 'deleteSkill']);
});

Route::group(["middleware" => "jwt.auth"], function () {
    Route::post('/skills/add-known-skill', [SkillController::class, 'addKnownSkill']);
    Route::post('/skills/remove-known-skill', [SkillController::class, 'removeKnownSkill']);
});

// position routes
Route::get('/positions/get-all', [PositionController::class, 'getAll']);
Route::get('/positions/get-by-id/{positionId}', [PositionController::class, 'getById']);
Route::get('/positions/get-by-keyword/{word}', [PositionController::class, 'getByKeyWords']);

Route::group(["middleware" => ["jwt.auth", "isRecruiter"]], function () {
    Route::post('/positions/new', [PositionController::class, 'newPosition']);
    Route::post('/positions/attach-skill', [PositionController::class, 'attachSkill']);
    Route::post('/positions/detach-skill', [PositionController::class, 'detachSkill']);
    Route::put('/positions/update/{positionId}', [PositionController::class, 'updatePosition']);
});

// application routes
Route::group(["middleware" => ["jwt.auth", "isRecruiter"]], function () {
    Route::get('/applications/get-by-position/{positionId}', [ApplicationController::class, 'getByPositionId']);
    Route::put('/applications/reject-application/{applicationId}', [ApplicationController::class, 'rejectApplication']);
});

Route::group(["middleware" => "jwt.auth"], function () {
    Route::get('/applications/my-applications', [ApplicationController::class, 'getMyApplications']);
    Route::post('/applications/apply', [ApplicationController::class, 'applyForPosition']);
});

// tests controller
Route::group(["middleware" => ["jwt.auth", "isRecruiter"]], function () {
    Route::post('/tests/new', [TestController::class, 'newTest']);
    Route::post('/tests/attach-skill', [TestController::class, 'attachSkill']);
});