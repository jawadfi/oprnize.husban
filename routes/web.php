<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Fallback GET logout for Filament company panel (Alpine.js may not intercept click on slow connections)
Route::get('/company/logout', function () {
    auth('company')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/company/login');
});

// Download bulk-assign Excel template (provider side)
Route::get('/company/bulk-assign-template', function () {
    $spreadsheet = \App\Filament\Company\Pages\ClientCompaniesListing::buildTemplateSpreadsheet();
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    return response()->streamDownload(function () use ($writer) {
        $writer->save('php://output');
    }, 'bulk-assign-template.xlsx', [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
})->middleware('auth:company');

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


