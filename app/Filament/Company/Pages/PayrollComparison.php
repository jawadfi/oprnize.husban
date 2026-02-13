<?php

namespace App\Filament\Company\Pages;

use App\Enums\EmployeeAssignedStatus;
use App\Models\Employee;
use App\Models\Payroll;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PayrollComparison extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string $view = 'filament.company.pages.payroll-comparison';
    
    protected static ?string $navigationLabel = 'مطابقة الرواتب';
    
    protected static ?string $title = 'Payroll Comparison - مطابقة الرواتب';
    
    protected static ?string $navigationGroup = 'Payroll';
    
    protected static ?int $navigationSort = 3;

    public ?string $selectedMonth = null;

    public function mount(): void
    {
        if (!$this->selectedMonth) {
            $this->selectedMonth = now()->format('Y-m');
        }
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        
        // Only accessible to Company models (not User models)
        if (!($user instanceof \App\Models\Company)) {
            return false;
        }
        
        return true;
    }

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();
        $month = $this->selectedMonth ?? now()->format('Y-m');

        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('employee.emp_id')
                    ->label('رقم الموظف')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('employee.name')
                    ->label('اسم الموظف')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('provider_payroll.basic_salary')
                    ->label('راتب الشركة الأم')
                    ->money('SAR')
                    ->getStateUsing(fn($record) => $record->provider_payroll?->basic_salary ?? 0)
                    ->color('info'),
                    
                TextColumn::make('provider_payroll.monthly_cost')
                    ->label('تكلفة الشركة الأم')
                    ->money('SAR')
                    ->getStateUsing(fn($record) => $record->provider_payroll?->monthly_cost ?? 0)
                    ->color('primary')
                    ->weight('bold'),
                    
                TextColumn::make('client_payroll.basic_salary')
                    ->label('راتب العميل')
                    ->money('SAR')
                    ->getStateUsing(fn($record) => $record->client_payroll?->basic_salary ?? 0)
                    ->color('warning'),
                    
                TextColumn::make('client_payroll.monthly_cost')
                    ->label('تكلفة العميل')
                    ->money('SAR')
                    ->getStateUsing(fn($record) => $record->client_payroll?->monthly_cost ?? 0)
                    ->color('danger')
                    ->weight('bold'),
                    
                TextColumn::make('difference')
                    ->label('الفرق')
                    ->money('SAR')
                    ->getStateUsing(function($record) {
                        $providerCost = $record->provider_payroll?->monthly_cost ?? 0;
                        $clientCost = $record->client_payroll?->monthly_cost ?? 0;
                        return abs($providerCost - $clientCost);
                    })
                    ->color(fn($record) => 
                        abs(($record->provider_payroll?->monthly_cost ?? 0) - ($record->client_payroll?->monthly_cost ?? 0)) > 100 
                        ? 'danger' 
                        : 'success'
                    ),
                    
                TextColumn::make('match_status')
                    ->label('الحالة')
                    ->badge()
                    ->getStateUsing(function($record) {
                        $providerCost = $record->provider_payroll?->monthly_cost ?? 0;
                        $clientCost = $record->client_payroll?->monthly_cost ?? 0;
                        
                        if ($providerCost > 0 && $clientCost > 0) {
                            if (abs($providerCost - $clientCost) < 10) {
                                return 'متطابق';
                            }
                            return 'فرق';
                        }
                        
                        if ($providerCost > 0) return 'الأم فقط';
                        if ($clientCost > 0) return 'العميل فقط';
                        
                        return 'غير محسوب';
                    })
                    ->color(fn($state) => match($state) {
                        'متطابق' => 'success',
                        'فرق' => 'warning',
                        'الأم فقط', 'العميل فقط' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('حالة المطابقة')
                    ->options([
                        'matched' => 'متطابق',
                        'different' => 'يوجد فرق',
                        'provider_only' => 'الشركة الأم فقط',
                        'client_only' => 'العميل فقط',
                        'not_calculated' => 'غير محسوب',
                    ])
                    ->query(function (Builder $query, $state) {
                        $month = $this->selectedMonth ?? now()->format('Y-m');
                        
                        return match($state['value'] ?? null) {
                            'matched' => $query->whereHas('provider_payroll', function($q) use ($month) {
                                    $q->where('payroll_month', $month);
                                })
                                ->whereHas('client_payroll', function($q) use ($month) {
                                    $q->where('payroll_month', $month);
                                }),
                            default => $query,
                        };
                    }),
            ])
            ->emptyStateHeading('لا توجد بيانات للمطابقة')
            ->emptyStateDescription('قم باحتساب الرواتب للشهر المحدد أولاً')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    protected function getTableQuery(): Builder
    {
        $user = Filament::auth()->user();
        $month = $this->selectedMonth ?? now()->format('Y-m');

        if ($user->type === \App\Enums\CompanyTypes::PROVIDER) {
            // Provider: show their employees that are assigned to clients
            return Employee::query()
                ->where('company_id', $user->id)
                ->whereHas('assigned', fn($q) => 
                    $q->where('status', EmployeeAssignedStatus::APPROVED)
                )
                ->with([
                    'provider_payroll' => fn($q) => $q->where('payroll_month', $month)->where('company_id', $user->id),
                    'client_payroll' => fn($q) => $q->where('payroll_month', $month)->whereHas('company', fn($c) => $c->where('type', 'client')),
                ]);
        } else {
            // Client: show assigned employees
            return Employee::query()
                ->whereHas('assigned', fn($q) => 
                    $q->where('employee_assigned.company_id', $user->id)
                      ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
                )
                ->with([
                    'provider_payroll' => fn($q) => $q->where('payroll_month', $month)->whereHas('company', fn($c) => $c->where('type', 'provider')),
                    'client_payroll' => fn($q) => $q->where('payroll_month', $month)->where('company_id', $user->id),
                ]);
        }
    }
}
