<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceRatingController;
use App\Http\Controllers\DeviceScreenController;
use App\Http\Controllers\DeviceSeriesController;
use App\Http\Controllers\DeviceSubTypesController;
use App\Http\Controllers\DeviceTypesController;
use App\Http\Controllers\SubCategoryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\ShippingCarrierController;
use App\Http\Controllers\ShippingCarrierServiceController;
use App\Http\Controllers\PageTypesController;
use App\Http\Controllers\LogisticCenterController;
use App\Http\Controllers\StoreTypesController;
use App\Http\Controllers\ListingController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/catalog', [CatalogController::class, 'index']);
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.list');
Route::get('catalog/filter', [CatalogController::class, 'filter'])->name('catalog.filter');
Route::get('/catalog/add', [CatalogController::class, 'create'])->name('catalog.create');
Route::post('/catalog/add', [CatalogController::class, 'store'])->name('catalog.store');
Route::post('/catalog/saveTabData', [CatalogController::class, 'saveTabData'])->name('catalog.save');
Route::post('/catalog/finalize', [CatalogController::class, 'finalize'])->name('catalog.final');
Route::get('/catalog/edit/{id}', [CatalogController::class, 'edit'])->name('catalog.edit');
Route::post('/catalog/edit/{id}', [CatalogController::class, 'update'])->name('catalog.update');
//Route::delete('/catalog/destroy', [CatalogController::class, 'destroy'])->name('catalog.delete');
Route::delete('/catalog/destroy/{id}', [CatalogController::class, 'destroy'])->name('catalog.delete');
Route::get('/catalog/{id}', [CatalogController::class, 'show'])->name('catalog.show');
Route::post('/upload-image', [CatalogController::class, 'uploadImage'])->name('catalog.upload');
Route::post('/set-default', [CatalogController::class, 'setDefaultImage'])->name('catalog.set_default');
Route::post('/set-image-order', [CatalogController::class, 'setImageOrder'])->name('catalog.set_image_order');
Route::post('catalog/getstoredetail', [CatalogController::class, 'getStoreDetail'])->name('catalog.getstoredetail');

Route::controller(StoreController::class)->group(function () {
    Route::get('/stores', 'index')->name('store.list');
    Route::get('/stores/add', 'create')->name('store.create');
    Route::post('/stores/saveTabData', 'saveTabData')->name('store.save');
    Route::post('/stores/finalize', 'finalize')->name('store.final');
    Route::get('/stores/edit/{id}', 'edit')->name('store.edit');
    Route::post('/stores/edit/{id}', 'update')->name('store.update');
    Route::delete('/stores/destroy/{id}', 'destroy')->name('store.delete');
    Route::post('/stores/removeshipping', 'removeShipping')->name('store.removeshipping');
    Route::post('/stores/saveShippingService', 'saveShippingService')->name('store.shippingservice');
    Route::post('/stores/savePages', 'savePages')->name('store.pages');
    Route::post('/stores/removepage', 'removePage')->name('store.removepage');
    Route::post('/stores/saveCategory', 'saveCategory')->name('store.category');
    Route::post('/stores/removecategory', 'removeCategory')->name('store.removecategory');
    Route::post('/stores/saveMessageAccount', 'saveMessageAccount')->name('store.messageaccount');
    Route::post('/stores/removemessageaccount', 'removeMessageAccount')->name('store.removemessageaccount');
    Route::post('/stores/saveLogisticCenter', 'saveLogisticCenter')->name('store.logisticcenter');
    Route::post('/stores/removelogisticcenter', 'removeLogisticCenter')->name('store.removelogisticcenter');
});

Route::controller(ListingController::class)->group(function () {
    Route::get('/listing', 'index')->name('listing.list');
    Route::get('/listing/add', 'create')->name('listing.create');
    Route::get('/listing/filter', 'filter')->name('listing.filter');
    Route::post('/listing/saveTabData', 'saveTabData')->name('listing.save');
    Route::post('/listing/finalize', 'finalize')->name('listing.final');
    Route::get('/listing/edit/{id}', 'edit')->name('listing.edit');
    Route::post('/listing/edit/{id}', 'update')->name('listing.update');
    Route::delete('/listing/destroy/{id}', 'destroy')->name('listing.delete');
});

Route::controller(StoreTypesController::class)->group(function () {
    Route::get('/store_type', 'index')->name('store_type.index');
    Route::get('/store_type/add', 'create')->name('store_type.create');
    Route::post('/store_type/add', 'store')->name('store_type.store');
    Route::get('/store_type/edit/{id}', 'edit')->name('store_type.edit');
    Route::post('/store_type/edit/{id}', 'update')->name('store_type.update');
    Route::get('/store_type/delete/{id}', 'destroy')->name('store_type.delete');
});

Route::controller(LogisticCenterController::class)->group(function () {
    Route::get('/logistic_center', 'index')->name('logistic_center.index');
    Route::get('/logistic_center/add', 'create')->name('logistic_center.create');
    Route::post('/logistic_center/add', 'store')->name('logistic_center.store');
    Route::get('/logistic_center/edit/{id}', 'edit')->name('logistic_center.edit');
    Route::post('/logistic_center/edit/{id}', 'update')->name('logistic_center.update');
    Route::get('/logistic_center/delete/{id}', 'destroy')->name('logistic_center.delete');
});

Route::controller(ShippingCarrierController::class)->group(function () {
    Route::get('/shipping_carrier', 'index')->name('shipping_carrier.index');
    Route::get('/shipping_carrier/add', 'create')->name('shipping_carrier.create');
    Route::post('/shipping_carrier/add', 'store')->name('shipping_carrier.store');
    Route::get('/shipping_carrier/edit/{id}', 'edit')->name('shipping_carrier.edit');
    Route::post('/shipping_carrier/edit/{id}', 'update')->name('shipping_carrier.update');
    Route::get('/shipping_carrier/delete/{id}', 'destroy')->name('shipping_carrier.delete');
});

Route::controller(ShippingCarrierServiceController::class)->group(function () {
    Route::get('/shipping_carrier_service', 'index')->name('shipping_carrier_service.index');
    Route::get('/shipping_carrier_service/add', 'create')->name('shipping_carrier_service.create');
    Route::post('/shipping_carrier_service/add', 'store')->name('shipping_carrier_service.store');
    Route::get('/shipping_carrier_service/edit/{id}', 'edit')->name('shipping_carrier_service.edit');
    Route::post('/shipping_carrier_service/edit/{id}', 'update')->name('shipping_carrier_service.update');
    Route::get('/shipping_carrier_service/delete/{id}', 'destroy')->name('shipping_carrier_service.delete');
});

Route::controller(PageTypesController::class)->group(function () {
    Route::get('/page_type', 'index')->name('page_type.index');
    Route::get('/page_type/add', 'create')->name('page_type.create');
    Route::post('/page_type/add', 'store')->name('page_type.store');
    Route::get('/page_type/edit/{id}', 'edit')->name('page_type.edit');
    Route::post('/page_type/edit/{id}', 'update')->name('page_type.update');
    Route::get('/page_type/delete/{id}', 'destroy')->name('page_type.delete');
});

Route::controller(BrandController::class)->group(function () {
    Route::get('/brands', 'index')->name('brand.list');
    Route::get('/brands/filter', 'filter')->name('brand.filter');
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

Route::controller(DeviceSeriesController::class)->group(function () {
    Route::get('/device_series', 'index')->name('device_series.index');
    Route::get('/device_series/add', 'create')->name('device_series.create');
    Route::post('/device_series/add', 'store')->name('device_series.store');
    Route::get('/device_series/edit/{id}', 'edit')->name('device_series.edit');
    Route::post('/device_series/edit/{id}', 'update')->name('device_series.update');
    Route::get('/device_series/delete/{id}', 'destroy')->name('device_series.delete');
});

Route::controller(DeviceRatingController::class)->group(function () {
    Route::get('/device_ratings', 'index')->name('device_ratings.index');
    Route::get('/device_ratings/add', 'create')->name('device_ratings.create');
    Route::post('/device_ratings/add', 'store')->name('device_ratings.store');
    Route::get('/device_ratings/edit/{id}', 'edit')->name('device_ratings.edit');
    Route::post('/device_ratings/edit/{id}', 'update')->name('device_ratings.update');
    Route::get('/device_ratings/delete/{id}', 'destroy')->name('device_ratings.delete');
});

Route::controller(DeviceScreenController::class)->group(function () {
    Route::get('/device_screen', 'index')->name('device_screen.index');
    Route::get('/device_screen/add', 'create')->name('device_screen.create');
    Route::post('/device_screen/add', 'store')->name('device_screen.store');
    Route::get('/device_screen/edit/{id}', 'edit')->name('device_screen.edit');
    Route::post('/device_screen/edit/{id}', 'update')->name('device_screen.update');
    Route::get('/device_screen/delete/{id}', 'destroy')->name('device_screen.delete');
});

Route::controller(DeviceController::class)->group(function () {
    Route::get('/devices', 'index')->name('devices.index');
    Route::get('/devices/add', 'create')->name('devices.create');
    Route::post('/devices/add', 'store')->name('devices.store');
    Route::get('/devices/edit/{id}', 'edit')->name('devices.edit');
    Route::post('/devices/edit/{id}', 'update')->name('devices.update');
    Route::get('/devices/delete/{id}', 'destroy')->name('devices.delete');
});

Route::controller(CustomerController::class)->group(function () {
    Route::get('/customers', 'index')->name('customers.index');
    Route::get('/customers/add', 'create')->name('customers.create');
    Route::post('/customers/add', 'store')->name('customers.store');
    Route::get('/customers/edit/{id}', 'edit')->name('customers.edit');
    Route::post('/customers/edit/{id}', 'update')->name('customers.update');
    Route::get('/customers/delete/{id}', 'destroy')->name('customers.delete');
});

Route::controller( CategoryController::class)->group(function () {
    Route::get('/categories', 'index')->name('categories.index');
    Route::get('/categories/add', 'create')->name('categories.create');
    Route::post('/categories/add', 'store')->name('categories.store');
    Route::get('/categories/edit/{id}', 'edit')->name('categories.edit');
    Route::post('/categories/edit/{id}', 'update')->name('categories.update');
    Route::get('/categories/delete/{id}', 'destroy')->name('categories.delete');
});

Route::controller(SubCategoryController::class)->group(function () {
    Route::get('/categories_sub', 'index')->name('categories_sub.index');
    Route::get('/categories_sub/add', 'create')->name('categories_sub.create');
    Route::post('/categories_sub/add', 'store')->name('categories_sub.store');
    Route::get('/categories_sub/edit/{id}', 'edit')->name('categories_sub.edit');
    Route::post('/categories_sub/edit/{id}', 'update')->name('categories_sub.update');
    Route::get('/categories_sub/delete/{id}', 'destroy')->name('categories_sub.delete');
});
