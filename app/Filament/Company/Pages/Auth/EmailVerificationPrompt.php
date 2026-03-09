<?php

namespace App\Filament\Company\Pages\Auth;

use DiogoGPinto\AuthUIEnhancer\Pages\Auth\EmailVerification\AuthUiEnhancerEmailVerificationPrompt;

class EmailVerificationPrompt extends AuthUiEnhancerEmailVerificationPrompt
{
    protected static string $view = 'filament.company.pages.auth.email-verification-prompt';
}
