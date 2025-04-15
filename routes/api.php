<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AddressListController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WialonController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// search address
//Route::get('get-address', [AddressController::class,'getAddressData']);

// update every 10 min addressList
Route::get('update-address-list', [AddressListController::class, 'updateAddressList']);

Route::prefix('wialon')->group(function () {
    Route::post('create-order', [WialonController::class, 'createOrder']);

    Route::post('driver-list', [WialonController::class, 'getDriverInfo']);
    Route::post('driver-details', [WialonController::class, 'getDriverInfo']);


    Route::get('unit-list', [WialonController::class, 'getUnitList']);
    Route::post('route-list', [WialonController::class, 'getRouteList']);
    Route::get('address-list', [WialonController::class, 'getAddressList']);

    // update unit description
    Route::post('update-geofence-guid', [WialonController::class, 'updateUnitDescription']);


    //get report
    Route::get('get-report', [WialonController::class, 'getReport']);
});
