<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('mail', function () {
    Mail::raw('Hello John, this is a simple plain-text message.', function ($message) {
        $message->to('mahmoudslameh95@gmail.com')
        ->subject('Simple Text Email');
    });
    dd('done');
});


