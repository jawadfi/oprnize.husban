<?php

namespace App\Filament\Company\Pages;
use Afsakar\FilamentOtpLogin\Filament\Pages\Login as OtpLogin;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;

class Login extends OtpLogin
{
    use HasCustomLayout;
}
