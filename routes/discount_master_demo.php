<?php

use Illuminate\Support\Facades\Route;

Route::get('/demo/discount-master', [App\Http\Controllers\Demo\DiscountMasterDemoController::class, 'index'])
    ->name('demo.discount-master.index');
