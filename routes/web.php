<?php

use App\Http\Controllers\BrandController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CatalogController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/catalog', [CatalogController::class, 'index']);
Route::get('/brands', [BrandController::class, 'index'])->name('brand.list');
Route::get('/brands/add', [BrandController::class, 'create'])->name('brand.create');
Route::post('/brands/add', [BrandController::class, 'store'])->name('brand.store');
