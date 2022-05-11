<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\UserBankController;
use App\Http\Controllers\FundTransferController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [LoginController::class, 'login']);

Route::group(['prefix' => 'wallet', 'middleware' => 'user'], function () {
    Route::get('/', [WalletController::class, 'index']);
    Route::get('/{wallet}', [WalletController::class, 'show']);
    Route::post('/', [WalletController::class, 'store']);
    Route::post('/fund', [WalletController::class, 'fundWallet']);
    Route::put('/{wallet}/status', [WalletController::class, 'updateStatus']);
});

Route::group(['prefix' => 'user-bank'], function () {
    Route::get('/', [UserBankController::class, 'index']);
});

Route::group(['prefix' => 'transfer', 'middleware' => 'user'], function () {
    Route::get('/', [FundTransferController::class, 'index']);
    Route::get('/{fundTransfer}', [FundTransferController::class, 'show']);
    Route::post('/', [FundTransferController::class, 'sendFunds']);
    Route::post('/withdraw', [FundTransferController::class, 'withdrawFunds']);
});