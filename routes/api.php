<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoController;

Route::get('/videos', [VideoController::class, 'index']);
Route::post('/videos', [VideoController::class, 'store']);
Route::get('/videos/{video}', [VideoController::class, 'show']);
Route::put('/videos/{video}', [VideoController::class, 'update']);
Route::delete('/videos/{video}', [VideoController::class, 'destroy']);
Route::post('/videos/import', [VideoController::class, 'import']);
Route::post('/videos/import/channel', [VideoController::class, 'importChannel']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
