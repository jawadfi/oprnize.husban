<?php

namespace App\Filament\Company\Pages;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;

class Login extends \Filament\Pages\Auth\Login
{
    use HasCustomLayout;

    protected function throwFailureValidationException(): never
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        
        // The custom CompanyUserProvider will handle both Company and User authentication
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];
        
        if (\Auth::guard('company')->attempt($credentials, $data['remember'] ?? false)) {
            $authenticatedUser = \Auth::guard('company')->user();
            
            // Store the model type in session to distinguish between Company and User
            // Note: Laravel's attempt() method already handles session regeneration for security
            session()->put('auth_model_type', get_class($authenticatedUser));
            
            return app(LoginResponse::class);
        }
        
        $this->throwFailureValidationException();
    }
}
