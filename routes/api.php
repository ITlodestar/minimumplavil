<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;

Route::get('/test', function () {
    return response()->json(['status' => true]);
});

Route::post('/user', [UserController::class, 'store']);
Route::patch('/user', [UserController::class, 'update']);
Route::get('/user/{user_tgid}', [UserController::class, 'getUserByTgid']);
Route::post('/check_wallet', [WalletController::class, 'checkWallet']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
