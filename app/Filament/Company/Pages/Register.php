<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyTypes;
use App\Models\City;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Illuminate\Validation\Rules\Password;
use JaOcero\RadioDeck\Forms\Components\RadioDeck;

class Register extends \Filament\Pages\Auth\Register
{
    use HasCustomLayout;

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Wizard::make()->steps([
                            Step::make('1')->schema([
                                RadioDeck::make('type')
                                    ->options([
                                        CompanyTypes::PROVIDER => '',
                                        CompanyTypes::CLIENT => '',
                                    ])->descriptions([
                                        CompanyTypes::PROVIDER => 'I am a company that provide HR manpower',
                                        CompanyTypes::CLIENT => 'I am a client who will receive service',
                                    ]) ->icons([
                                        CompanyTypes::PROVIDER => 'heroicon-o-building-office-2',
                                        CompanyTypes::CLIENT => 'heroicon-o-building-office-2',
                                    ])->required()
                                    ->color('primary')
                                    ->gap('gap-5') // Gap between Options and Descriptions between the Icon
                                    ->extraCardsAttributes([ // Extra attributes for card elements
                                        'class' => 'rounded-xl'
                                    ])
                                    ->extraOptionsAttributes([ // Extra attributes for option elements
                                        'class' => 'text-3xl leading-none w-full flex flex-col items-center justify-center p-4'
                                    ])
                                    ->extraDescriptionsAttributes([ // Extra attributes for description elements
                                        'class' => 'text-sm font-light text-center'
                                    ])
                            ])->hiddenLabel(),
                            Step::make('2')->schema([
                                TextInput::make('name')
                                    ->hiddenLabel()
                                    ->placeholder('Enter Company Name')
                                    ->prefixIcon('heroicon-o-building-office-2'),
                                TextInput::make('commercial_registration_number')
                                    ->prefixIcon('heroicon-s-numbered-list')
                                    ->hiddenLabel()
                                    ->placeholder('Enter company registration number')
                                    ->prefixIcon('heroicon-o-building-office-2'),
                                self::getEmailFormComponent(),
                                self::getPasswordFormComponent(),
                                Select::make('city_id')
                                ->hiddenLabel()
                                ->placeholder('Select your city')
                                ->options(City::pluck('name','id'))
                                ->preload()
                                ->searchable()

                            ])->hiddenLabel()
                        ])
                    ])
                    ->statePath('data'),
            ),
        ];
    }
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/register.form.password.label'))
            ->password()
            ->hiddenLabel()
            ->prefixIcon('heroicon-o-lock-closed')
            ->placeholder('Enter your password')
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute'));
    }
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/register.form.email.label'))
            ->email()
            ->hiddenLabel()
            ->placeholder('Enter your email')
            ->prefixIcon('heroicon-o-envelope')
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }
}
