<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Tenancy;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', function () {
            dd(Tenancy::getTenant());
            return view('welcome');
        });
    });
}
