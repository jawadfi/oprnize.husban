<?php

namespace App\Filament\Company\Pages;

use App\Filament\Company\Resources\RoleResource;
use App\Filament\Company\Resources\UserResource;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'الإعدادات / Settings';

    protected static ?string $title = 'الإعدادات / Settings';

    protected static ?int $navigationSort = 98;

    protected static string $view = 'filament.company.pages.settings';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if ($user instanceof Company) {
            return true;
        }

        if ($user instanceof User) {
            if ($user->isBranchManager()) {
                return false;
            }

            return $user->can('view_any_user')
                || $user->can('view_any_UserResource')
                || $user->can('view_any_role')
                || $user->can('view_any_Role')
                || $user->can('page_AuditTrail');
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getLinks(): array
    {
        return [
            [
                'title' => 'Users',
                'description' => 'إدارة المستخدمين',
                'url' => UserResource::getUrl('index'),
                'icon' => 'heroicon-o-users',
            ],
            [
                'title' => 'Roles',
                'description' => 'إدارة الأدوار والصلاحيات',
                'url' => RoleResource::getUrl('index'),
                'icon' => 'heroicon-o-shield-check',
            ],
            [
                'title' => 'Audit Trail',
                'description' => 'سجل التدقيق والعمليات',
                'url' => AuditTrail::getUrl(),
                'icon' => 'heroicon-o-clipboard-document-check',
            ],
        ];
    }
}
