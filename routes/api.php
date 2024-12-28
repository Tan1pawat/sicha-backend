<?php

use App\Http\Controllers\BillController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\PrisonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//product
Route::resource('product', ProductController::class);
Route::post('/product_page', [ProductController::class, 'getPage']);
Route::get('/get_product', [ProductController::class, 'getList']);

//product_type
Route::get('/get_product_type', [ProductTypeController::class, 'getList']);
Route::resource('product_type', ProductTypeController::class);

//unit
Route::get('/get_unit', [UnitController::class, 'getList']);
Route::resource('unit', UnitController::class);

//prison
Route::get('/get_prison', [PrisonController::class, 'getList']);
Route::resource('prison', PrisonController::class);

//company
Route::get('/get_company', [CompanyController::class, 'getList']);
Route::resource('company', CompanyController::class);

//bill
Route::resource('bill', BillController::class);
Route::post('/bill_page', [BillController::class, 'getPage']);

//general
Route::post('/upload_product_image',[Controller::class,'uploadProductImage']);