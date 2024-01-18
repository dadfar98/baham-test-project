<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\FollowingController;

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

Route::post('/register', [AuthController::class, 'register'])->name('register');

Route::post('/login', [AuthController::class, 'login'])->name('login');


Route::group(['middleware' => ['auth:sanctum']], function (){
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('/orders/create', [OrderController::class, 'store'])->name('create-order');

    Route::get('/orders/ongoing', [OrderController::class, 'getOngoingOrders'])->name('get-ongoing-orders');

    Route::post('/orders/follow/{orderToFollow}', [OrderController::class, 'followByOrder'])->name('follow-by-order');

    Route::post('/orders/buy-coins', [OrderController::class, 'buyCoins'])->name('buy-coins');

    Route::post('/following/follow/{userToFollow}', [FollowingController::class, 'follow'])->name('follow');

});
