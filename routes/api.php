<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\DeviceInterfaceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImagesController;
use App\Http\Controllers\InterfaceController;
use App\Http\Controllers\UPCController;
use Illuminate\Support\Facades\Route;

Route::post('/upload-image', [ImagesController::class, 'uploadImage'])->name('images.upload');
Route::get('/get-image/{referenceId}', [ImagesController::class, 'getImage']);


Route::post('/brands/data', [BrandController::class, 'data']);


Route::get('/listing-feeds', [HomeController::class, 'getListingData'])->name('listing-data');
Route::get('/interface-data', [InterfaceController::class, 'getInterfaceData'])->name('interface-data');
Route::get('/device-interface-data', [DeviceInterfaceController::class, 'getDeviceInterfaceData'])->name('device-interface-data');
Route::get('/product-interface-data', [\App\Http\Controllers\ProductInterfaceController::class, 'getProductInterfaceData'])->name('product-interface-data');

Route::get('/stores', [UPCController::class, 'getStoresData']);
