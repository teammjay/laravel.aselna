<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\DeviceSubTypesController;
use App\Http\Controllers\DeviceTypesController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CatalogController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/catalog', [CatalogController::class, 'index']);
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.list');
Route::get('catalog/filter', [CatalogController::class, 'filter'])->name('catalog.filter');
Route::get('/catalog/add', [CatalogController::class, 'create'])->name('catalog.create');
Route::post('/catalog/add', [CatalogController::class, 'store'])->name('catalog.store');
Route::post('/catalog/saveTabData', [CatalogController::class, 'store'])->name('catalog.save');
Route::get('/catalog/edit/{id}', [CatalogController::class, 'edit'])->name('catalog.edit');
Route::post('/catalog/edit/{id}', [CatalogController::class, 'update'])->name('catalog.update');
//Route::delete('/catalog/destroy', [CatalogController::class, 'destroy'])->name('catalog.delete');
Route::delete('/catalog/destroy/{id}', [CatalogController::class, 'destroy'])->name('catalog.delete');
Route::get('/catalog/{id}', [CatalogController::class, 'show'])->name('catalog.show');

Route::controller(BrandController::class)->group(function () {
    Route::get('/brands', 'index')->name('brand.list');
    Route::get('/brands/add', 'create')->name('brand.create');
    Route::post('/brands/add', 'store')->name('brand.store');
    Route::get('/brands/edit/{id}', 'edit')->name('brand.edit');
    Route::post('/brands/edit/{id}', 'update')->name('brand.update');
    Route::get('/brands/delete/{id}', 'destroy')->name('brand.delete');
});

Route::controller(DeviceTypesController::class)->group(function () {
    Route::get('/device_types', 'index')->name('device_types.list');
    Route::get('/device_types/add', 'create')->name('device_types.create');
    Route::post('/device_types/add', 'store')->name('device_types.store');
    Route::get('/device_types/edit/{id}', 'edit')->name('device_types.edit');
    Route::post('/device_types/edit/{id}', 'update')->name('device_types.update');
    Route::get('/device_types/delete/{id}', 'destroy')->name('device_types.delete');
});

Route::controller(DeviceSubTypesController::class)->group(function () {
    Route::get('/device_sub_types', 'index')->name('device_sub_types.list');
    Route::get('/device_sub_types/add', 'create')->name('device_sub_types.create');
    Route::post('/device_sub_types/add', 'store')->name('device_sub_types.store');
    Route::get('/device_sub_types/edit/{id}', 'edit')->name('device_sub_types.edit');
    Route::post('/device_sub_types/edit/{id}', 'update')->name('device_sub_types.update');
    Route::get('/device_sub_types/delete/{id}', 'destroy')->name('device_sub_types.delete');
});
