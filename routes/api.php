<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\ImagesController;
use Illuminate\Support\Facades\Route;

Route::post('/upload-image', [ImagesController::class, 'uploadImage'])->name('images.upload');
Route::get('/get-image/{referenceId}', [ImagesController::class, 'getImage']);


Route::post('/brands/data', [BrandController::class, 'data']);
