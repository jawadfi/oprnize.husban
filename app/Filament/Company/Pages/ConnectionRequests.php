<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyConnectionStatus;
use App\Enums\CompanyTypes;
use App\Models\CompanyConnection;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

class ConnectionRequests extends Page implements HasActions
{
    use WithPagination;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static string $view = 'filament.company.pages.connection-requests';

    protected static ?string $navigationLabel = 'طلبات الربط / Connections';

    protected static ?string $title = 'طلبات الربط / Connection Requests';

    protected static ?int $navigationSort = 2;

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if ($user instanceof \App\Models\Company) {
            return $user->type === CompanyTypes::CLIENT;
        }

        if ($user instanceof User) {
            return $user->company?->type === CompanyTypes::CLIENT;
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    // ─── Data ─────────────────────────────────────────────────────────────────

    protected function getClientCompanyId(): ?int
    {
        $user = Filament::auth()->user();
        return $user instanceof \App\Models\Company
            ? $user->id
            : ($user instanceof User ? $user->company_id : null);
    }

    public function getPendingRequestsProperty(): LengthAwarePaginator
    {
        $clientId = $this->getClientCompanyId();

        return CompanyConnection::with('provider')
            ->where('client_company_id', $clientId)
            ->where('status', CompanyConnectionStatus::PENDING->value)
            ->latest()
            ->paginate(10, pageName: 'pending_page');
    }

    public function getAllRequestsProperty(): LengthAwarePaginator
    {
        $clientId = $this->getClientCompanyId();

        return CompanyConnection::with('provider')
            ->where('client_company_id', $clientId)
            ->whereIn('status', [
                CompanyConnectionStatus::APPROVED->value,
                CompanyConnectionStatus::DECLINED->value,
            ])
            ->latest()
            ->paginate(10, pageName: 'all_page');
    }

    // ─── Actions ──────────────────────────────────────────────────────────────

    public function approveConnectionAction(): Action
    {
        return Action::make('approveConnection')
            ->label('موافقة / Approve')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(function (array $arguments): string {
                $providerName = CompanyConnection::with('provider')
                    ->find($arguments['connectionId'] ?? 0)?->provider?->name ?? '';
                return "الموافقة على ربط شركة: {$providerName}";
            })
            ->modalDescription('بعد الموافقة يمكن لشركة المورد تعيين موظفيها لديكم.')
            ->modalSubmitActionLabel('موافقة / Approve')
            ->action(function (array $arguments): void {
                $connection = CompanyConnection::find($arguments['connectionId'] ?? 0);

                if (! $connection || $connection->client_company_id !== $this->getClientCompanyId()) {
                    Notification::make()->title('خطأ: طلب غير موجود')->danger()->send();
                    return;
                }

                $connection->update(['status' => CompanyConnectionStatus::APPROVED->value]);

                Notification::make()
                    ->title('تمت الموافقة على الربط / Connection approved')
                    ->success()
                    ->send();
            });
    }

    public function declineConnectionAction(): Action
    {
        return Action::make('declineConnection')
            ->label('رفض / Decline')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(function (array $arguments): string {
                $providerName = CompanyConnection::with('provider')
                    ->find($arguments['connectionId'] ?? 0)?->provider?->name ?? '';
                return "رفض طلب ربط شركة: {$providerName}";
            })
            ->modalDescription('هل أنت متأكد من رفض هذا الطلب؟ يمكن للشركة إعادة الإرسال لاحقاً.')
            ->modalSubmitActionLabel('رفض / Decline')
            ->action(function (array $arguments): void {
                $connection = CompanyConnection::find($arguments['connectionId'] ?? 0);

                if (! $connection || $connection->client_company_id !== $this->getClientCompanyId()) {
                    Notification::make()->title('خطأ: طلب غير موجود')->danger()->send();
                    return;
                }

                $connection->update(['status' => CompanyConnectionStatus::DECLINED->value]);

                Notification::make()
                    ->title('تم رفض الطلب / Request declined')
                    ->warning()
                    ->send();
            });
    }
}
