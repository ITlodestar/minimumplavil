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

Route::get('/user/{user_tgid}', [UserController::class, 'getUserByTgid']);
Route::get('/first_deposit/{deposit_account_id}', [UserController::class, 'getFirstDeposit']);
Route::post('/user', [UserController::class, 'store']);//->middleware('remap_fields');
Route::post('/check_wallet', [WalletController::class, 'checkWallet']);
Route::post('/update_wallet', [WalletController::class, 'updateWallet']);
Route::post('/deposit', [UserController::class, 'deposit']);
Route::post('/create_deposit_account', [UserController::class, 'createDepositAccount']);
Route::post('/transfer', [UserController::class, 'transfer']);
Route::post('/tgtest', [UserController::class, 'tgTest']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
