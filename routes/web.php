<?php

use App\Http\Controllers\BrandController;
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
Route::get('/catalog/edit/{id}', [CatalogController::class, 'edit'])->name('catalog.edit');
Route::post('/catalog/edit/{id}', [CatalogController::class, 'update'])->name('catalog.update');
//Route::delete('/catalog/destroy', [CatalogController::class, 'destroy'])->name('catalog.delete');
Route::delete('/catalog/destroy/{id}', [CatalogController::class, 'destroy'])->name('catalog.delete');
Route::get('/catalog/{id}', [CatalogController::class, 'show'])->name('catalog.show');

Route::get('/brands', [BrandController::class, 'index'])->name('brand.list');
Route::get('/brands/add', [BrandController::class, 'create'])->name('brand.create');
Route::post('/brands/add', [BrandController::class, 'store'])->name('brand.store');
Route::get('/brands/edit/{id}', [BrandController::class, 'edit'])->name('brand.edit');
Route::post('/brands/edit/{id}', [BrandController::class, 'update'])->name('brand.update');
Route::get('/brands/delete/{id}', [BrandController::class, 'destroy'])->name('brand.delete');
Route::get('/brands/{id}', [BrandController::class, 'show'])->name('brand.show');
