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
Route::get('/brands/edit/{id}', [BrandController::class, 'edit'])->name('brand.edit');
Route::post('/brands/edit/{id}', [BrandController::class, 'update'])->name('brand.update');
Route::get('/brands/delete/{id}', [BrandController::class, 'destroy'])->name('brand.delete');
Route::get('/brands/{id}', [BrandController::class, 'show'])->name('brand.show');
