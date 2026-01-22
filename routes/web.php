<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/roleCompany', function () {
    /** @var \App\Models\Company $company */
    foreach (\App\Models\Company::all() as $company)
        $company->assignRole('super_admin');
});
Route::get('mail', function () {
    Mail::raw('Hello John, this is a simple plain-text message.', function ($message) {
        $message->to('mahmoudslameh95@gmail.com')
        ->subject('Simple Text Email');
    });
    dd('done');
});


