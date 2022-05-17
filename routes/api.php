<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\UserBankController;
use App\Http\Controllers\FundTransferController;
use App\Http\Controllers\BankController;

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

Route::get('/', function (Request $request) {
    return 'Hello!! Welcome to Brass Test App';
});

Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [LoginController::class, 'login']);

Route::get('authenticated-user', [LoginController::class, 'getAuthenticatedUser'])->middleware('user');

Route::group(['prefix' => 'wallet', 'middleware' => 'user'], function () {
    Route::get('/', [WalletController::class, 'index']);
    Route::get('/{wallet}', [WalletController::class, 'show']);
    Route::post('/', [WalletController::class, 'store']);
    Route::post('/fund/{wallet}', [WalletController::class, 'fundWallet']);
    Route::put('/activate/{wallet}', [WalletController::class, 'activateWallet']);
    Route::put('/deactivate/{wallet}', [WalletController::class, 'deactivateWallet']);
});

Route::group(['prefix' => 'user-bank', 'middleware' => 'user'], function () {
    Route::get('/', [UserBankController::class, 'index']);
    Route::get('/{userBank}', [UserBankController::class, 'show']);
    Route::post('/', [UserBankController::class, 'store']);
    Route::delete('/{userBank}', [UserBankController::class, 'destroy']);
});

Route::group(['prefix' => 'transfer', 'middleware' => 'user'], function () {
    Route::get('/', [FundTransferController::class, 'index']);
    Route::get('/{fundTransfer}', [FundTransferController::class, 'show']);
    Route::post('/wallet/{wallet}', [FundTransferController::class, 'sendFundsToAWallet']);
    Route::post('/wallet/{wallet}/bank', [FundTransferController::class, 'withdrawFundsToBank']);
});

Route::group(['prefix' => 'bank'], function () {
    Route::get('/', [BankController::class, 'index']);
});

Route::post('/paystack-webhook', [FundTransferController::class, 'paystackWebhook']);