<?php

namespace App\Filament\Employee\Pages;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveType;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class MyProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static string $view = 'filament.employee.pages.my-profile';

    protected static ?string $navigationLabel = 'ملفي الشخصي / My Profile';

    protected static ?int $navigationSort = 1;

    public function getTitle(): string | Htmlable
    {
        return 'ملفي الشخصي / My Profile';
    }

    public function getHeading(): string | Htmlable
    {
        return 'ملفي الشخصي / My Profile';
    }

    public function getEmployee()
    {
        return Filament::auth()->user();
    }

    public function getLeaveStats(): array
    {
        $employee = $this->getEmployee();
        $entitlement = $employee->annual_leave_entitlement ?? 21;
        $remaining   = $employee->vacation_balance ?? $entitlement;
        $used        = max(0, $entitlement - $remaining);

        // Approved annual leaves this year
        $usedThisYear = $employee->leaveRequests()
            ->where('leave_type', LeaveType::ANNUAL)
            ->where('status', LeaveRequestStatus::APPROVED)
            ->whereYear('start_date', now()->year)
            ->sum('days_count');

        $pending = $employee->leaveRequests()
            ->whereNotIn('status', [LeaveRequestStatus::APPROVED, LeaveRequestStatus::REJECTED])
            ->count();

        return [
            'entitlement'   => $entitlement,
            'remaining'     => $remaining,
            'used'          => $usedThisYear,
            'pending_count' => $pending,
        ];
    }

    public function getPassportStatus(): array
    {
        $employee = $this->getEmployee();

        if (!$employee->passport_expiry) {
            return ['status' => 'missing', 'label' => 'غير مُدخل', 'color' => '#c53030', 'bg' => '#fff5f5', 'border' => '#fc8181'];
        }

        $monthsLeft = now()->diffInMonths($employee->passport_expiry, false);

        if ($monthsLeft < 0) {
            return ['status' => 'expired', 'label' => 'منتهي الصلاحية ⚠️', 'color' => '#c53030', 'bg' => '#fff5f5', 'border' => '#fc8181'];
        }

        if ($monthsLeft < 6) {
            return ['status' => 'expiring', 'label' => 'ينتهي قريباً (' . $monthsLeft . ' شهر)', 'color' => '#b7791f', 'bg' => '#fffbeb', 'border' => '#f6ad55'];
        }

        return ['status' => 'valid', 'label' => 'سارٍ ✓', 'color' => '#276749', 'bg' => '#f0fff4', 'border' => '#9ae6b4'];
    }

    public function getVisaStatus(): array
    {
        $employee = $this->getEmployee();

        if (!$employee->visa_expiry) {
            return ['status' => 'missing', 'label' => 'غير مُدخلة', 'color' => '#718096', 'bg' => '#f7fafc', 'border' => '#e2e8f0'];
        }

        $monthsLeft = now()->diffInMonths($employee->visa_expiry, false);

        if ($monthsLeft < 0) {
            return ['status' => 'expired', 'label' => 'منتهية الصلاحية ⚠️', 'color' => '#c53030', 'bg' => '#fff5f5', 'border' => '#fc8181'];
        }

        if ($monthsLeft < 3) {
            return ['status' => 'expiring', 'label' => 'تنتهي قريباً (' . $monthsLeft . ' شهر)', 'color' => '#b7791f', 'bg' => '#fffbeb', 'border' => '#f6ad55'];
        }

        return ['status' => 'valid', 'label' => 'سارية ✓', 'color' => '#276749', 'bg' => '#f0fff4', 'border' => '#9ae6b4'];
    }
}
