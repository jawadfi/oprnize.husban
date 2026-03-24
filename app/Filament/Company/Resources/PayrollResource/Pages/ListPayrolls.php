<?php

namespace App\Filament\Company\Resources\PayrollResource\Pages;

use App\Filament\Company\Resources\PayrollResource;
use App\Filament\Company\Widgets\DeductionStatsWidget;
use App\Filament\Company\Widgets\EmployeeStatsWidget;
use App\Filament\Company\Widgets\LeaveRequestStatsWidget;
use App\Filament\Company\Widgets\PayrollStatsWidget;
use App\Enums\CompanyTypes;
use App\Enums\EmployeeAssignedStatus;
use App\Enums\PayrollStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ListPayrolls extends ListRecords
{
    use WithFileUploads;
    protected static string $resource = PayrollResource::class;

    protected static string $view = 'filament.company.resources.payroll-resource.pages.list-payrolls';

    #[Url]
    public ?string $selectedMonth = null;

    #[Url]
    public ?string $clientCompany = null;

    #[Url]
    public ?string $providerCompany = null;

    #[Url]
    public ?string $payrollCategory = null;

    public ?string $clientCompanyName = null;
    public ?string $providerCompanyName = null;
    public ?string $reviewApprovalCode = null;

    public $salaryFile = null;
    public bool $showSalaryImport = false;

    public function mount(): void
    {
        parent::mount();
        
        if (!$this->selectedMonth) {
            $this->selectedMonth = now()->format('Y-m');
        }

        if ($this->payrollCategory === 'review') {
            $this->reviewApprovalCode = (string) random_int(1000, 9999);
        }

        // Load client company name for display (PROVIDER view)
        if ($this->clientCompany && $this->clientCompany !== 'all') {
            if ($this->clientCompany === 'in_house') {
                $this->clientCompanyName = 'موظفين داخليين / In-House';
            } elseif ($this->clientCompany === 'no_payroll') {
                $this->clientCompanyName = 'بدون رواتب / No Payroll';
            } else {
                $company = Company::find($this->clientCompany);
                $this->clientCompanyName = $company?->name;
            }
        }

        // Load provider company name for display (CLIENT view)
        if ($this->providerCompany && $this->providerCompany !== 'all') {
            $company = Company::find($this->providerCompany);
            $this->providerCompanyName = $company?->name;
        }

        if ($this->payrollCategory === 'review' && ! $this->hasReviewEligiblePayrolls()) {
            Notification::make()
                ->title('صفحة المراجعة غير متاحة')
                ->body('تظهر صفحة مراجعة الرواتب فقط بعد إجراء تشغيل الرواتب لهذا الشهر.')
                ->warning()
                ->send();

            $this->redirect(PayrollResource::getUrl('index'));
            return;
        }
    }

    protected function getCurrentCompanyId(): ?int
    {
        $auth = Filament::auth()->user();

        if ($auth instanceof Company) {
            return $auth->id;
        }

        if ($auth instanceof User) {
            return $auth->company_id;
        }

        return null;
    }

    protected function getCurrentCompanyType(): ?string
    {
        $auth = Filament::auth()->user();

        if ($auth instanceof Company) {
            return $auth->type;
        }

        if ($auth instanceof User) {
            return $auth->company?->type;
        }

        return null;
    }

    public function getTitle(): string
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $categoryLabels = [
            'contracted' => 'Contracted Payroll',
            'run' => 'Run Payroll',
            'review' => 'Review',
        ];
        $title = ($categoryLabels[$this->payrollCategory] ?? 'Payroll') . ' - ' . $date->format('F Y');
        if ($this->clientCompanyName) {
            $title .= ' - ' . $this->clientCompanyName;
        }
        if ($this->providerCompanyName) {
            $title .= ' - ' . $this->providerCompanyName;
        }
        return $title;
    }

    protected function getHeaderActions(): array
    {
        if ($this->payrollCategory === 'contracted') {
            return [];
        }

        $companyType = $this->getCurrentCompanyType();

        if ($this->payrollCategory === 'review') {
            if ($companyType !== CompanyTypes::PROVIDER) {
                return [];
            }

            return [
                Actions\Action::make('approve_review')
                    ->label('قبول')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('approval_code_hint')
                            ->label('رمز التأكيد')
                            ->content(fn() => 'اكتب رقم التأكيد التالي لإتمام الاعتماد: ' . $this->reviewApprovalCode),
                        \Filament\Forms\Components\TextInput::make('confirmation_code')
                            ->label('رقم التأكيد')
                            ->required()
                            ->maxLength(4)
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail): void {
                                    if ((string) $value !== (string) $this->reviewApprovalCode) {
                                        $fail('رقم التأكيد غير صحيح.');
                                    }
                                };
                            }),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('قبول الرواتب')
                    ->modalDescription('سيتم قبول جميع رواتب المراجعة الظاهرة وإنشاء الفاتورة الضريبية تلقائياً.')
                    ->action(function (): void {
                        $reviewQuery = $this->getReviewActionQuery();
                        $firstPayroll = (clone $reviewQuery)->first();

                        $payrollIds = (clone $reviewQuery)->pluck('id');
                        $count = $payrollIds->count();

                        $payrolls = Payroll::query()->whereIn('id', $payrollIds)->get();

                        foreach ($payrolls as $payroll) {
                            /** @var Payroll $payroll */
                            $payroll->update([
                                'status' => PayrollStatus::FINALIZED,
                                'is_modified' => false,
                                'reback_reason' => null,
                                'provider_review_status' => 'accepted',
                                'provider_reviewed_at' => now(),
                                'provider_rejection_reason' => null,
                                'tax_invoice_number' => $payroll->tax_invoice_number ?: $this->generateTaxInvoiceNumber($payroll),
                                'tax_invoice_issued_at' => now(),
                                'tax_invoice_amount' => $payroll->monthly_cost,
                                'finalized_at' => now(),
                            ]);
                        }

                        if ($count === 0) {
                            Notification::make()
                                ->title('لا توجد رواتب لاعتمادها')
                                ->warning()
                                ->send();
                            return;
                        }

                        $this->reviewApprovalCode = (string) random_int(1000, 9999);
                        $this->resetTable();

                        $user = Filament::auth()->user();

                        if ($firstPayroll) {
                            activity('payroll')
                                ->causedBy($user)
                                ->performedOn($firstPayroll)
                                ->event('updated')
                                ->withProperties([
                                    'action' => 'provider_review_approved',
                                    'payroll_category' => $this->payrollCategory,
                                    'selected_month' => $this->selectedMonth,
                                    'client_company' => $this->clientCompany,
                                    'affected_count' => $count,
                                    'tax_invoice_created' => true,
                                ])
                                ->log('قبول رواتب المراجعة وإنشاء فاتورة ضريبية تلقائياً / Review payroll accepted with automatic tax invoice');
                        }

                        Notification::make()
                            ->title('تم قبول الرواتب')
                            ->body("تم قبول {$count} كشف راتب وإنشاء الفاتورة الضريبية تلقائياً")
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('return_review')
                    ->label('رفض')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reback_reason')
                            ->label('سبب الرفض')
                            ->required()
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('رفض الرواتب')
                    ->modalDescription('سيتم رفض جميع الرواتب الظاهرة، ولن يتم إصدار أي فاتورة حتى تتم المعالجة.')
                    ->action(function (array $data): void {
                        $reviewQuery = $this->getReviewActionQuery();
                        $firstPayroll = (clone $reviewQuery)->first();

                        $count = $reviewQuery->update([
                            'status' => PayrollStatus::REBACK,
                            'reback_reason' => $data['reback_reason'],
                            'is_modified' => true,
                            'provider_review_status' => 'rejected',
                            'provider_reviewed_at' => now(),
                            'provider_rejection_reason' => $data['reback_reason'],
                            'tax_invoice_number' => null,
                            'tax_invoice_issued_at' => null,
                            'tax_invoice_amount' => null,
                        ]);

                        if ($count === 0) {
                            Notification::make()
                                ->title('لا توجد رواتب لإعادتها')
                                ->warning()
                                ->send();
                            return;
                        }

                        $this->resetTable();

                        $user = Filament::auth()->user();

                        if ($firstPayroll) {
                            activity('payroll')
                                ->causedBy($user)
                                ->performedOn($firstPayroll)
                                ->event('updated')
                                ->withProperties([
                                    'action' => 'provider_review_returned',
                                    'payroll_category' => $this->payrollCategory,
                                    'selected_month' => $this->selectedMonth,
                                    'client_company' => $this->clientCompany,
                                    'affected_count' => $count,
                                    'reback_reason' => $data['reback_reason'],
                                    'tax_invoice_created' => false,
                                ])
                                ->log('رفض رواتب المراجعة ولن تصدر فاتورة / Review payroll rejected without invoice');
                        }

                        Notification::make()
                            ->title('تم رفض الرواتب')
                            ->body("تم رفض {$count} كشف راتب ولن تصدر أي فاتورة")
                            ->warning()
                            ->send();
                    }),
            ];
        }

        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        // Keep only the custom cards in the page view, because these are built
        // from the current selected company/month/category context.
        return [];
    }

    public function exportPayroll()
    {
        // Get the current filtered/searched payroll records
        $payrolls = $this->getTableQuery()->with('employee')->get();
        
        if ($payrolls->isEmpty()) {
            Notification::make()
                ->title('No data to export')
                ->warning()
                ->send();
            return;
        }
        
        // Simple CSV export
        $filename = 'payroll_' . $this->selectedMonth . '_' . now()->format('YmdHis') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=\"' . $filename . '\"',
        ];
        
        $callback = function() use ($payrolls) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Emp. ID',
                'Emp. Name',
                'Basic Salary',
                'Housing Allowance',
                'Transportation Allow',
                'Food Allowance',
                'Other Allowance',
                'Total Other Allowance',
                'Total Salary',
                'Fees',
                'Monthly Cost',
                'Overtime Amount',
                'Net Payment'
            ]);
            
            // CSV Data
            foreach ($payrolls as $payroll) {
                fputcsv($file, [
                    $payroll->employee->emp_id ?? 'N/A',
                    $payroll->employee->name ?? 'N/A',
                    $payroll->basic_salary,
                    $payroll->housing_allowance,
                    $payroll->transportation_allowance,
                    $payroll->food_allowance,
                    $payroll->other_allowance,
                    $payroll->total_other_allow,
                    $payroll->total_salary,
                    $payroll->fees,
                    $payroll->monthly_cost,
                    $payroll->overtime_amount ?? 0,
                    $payroll->net_payment
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    public function updatedTableSearch(): void
    {
        $this->resetTable();
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $user = Filament::auth()->user();
        $companyType = $this->getCurrentCompanyType();
        $companyId = $this->getCurrentCompanyId();

        if (! $companyId) {
            return $query->whereKey(-1);
        }

        // Contracted payroll for CLIENT should read the provider-side payroll data
        // so both parties can see the same contractual salary list.
        if ($this->payrollCategory === 'contracted' && $companyType === CompanyTypes::CLIENT) {
            $query = Payroll::query()
                ->whereHas('company', fn($q) => $q->where('type', CompanyTypes::PROVIDER))
                ->whereHas('employee.assigned', fn($q) =>
                    $q->where('employee_assigned.company_id', $companyId)
                        ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
                );
        }
        
        // Filter by selected client company (for PROVIDER)
        if ($this->clientCompany && $this->clientCompany !== 'all') {
            if ($this->clientCompany === 'in_house') {
                // In-House: employees NOT assigned to any client company
                $query->whereHas('employee', fn($q) =>
                    $q->whereDoesntHave('assigned', fn($sq) =>
                        $sq->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    )
                );
            } elseif ($this->clientCompany === 'no_payroll') {
                // No Payroll: show only payrolls with basic_salary = 0 (empty records)
                // These are auto-created when user clicks the No Payroll card
                $query->where('basic_salary', 0);
            } else {
                $clientId = (int) $this->clientCompany;
                $query->whereHas('employee.assigned', fn($q) =>
                    $q->where('employee_assigned.company_id', $clientId)
                      ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                );
            }
        }

        // Filter by selected provider company (for CLIENT)
        if ($this->providerCompany && $this->providerCompany !== 'all') {
            $providerId = (int) $this->providerCompany;
            $query->whereHas('employee', fn($q) =>
                $q->where('company_id', $providerId)
            );
        }

        // Filter by payroll_month field
        if ($this->selectedMonth) {
            $query->where(function ($q) {
                $q->where('payroll_month', $this->selectedMonth)
                  ->orWhere(function ($sq) {
                      // Fallback for old records without payroll_month
                      $date = Carbon::parse($this->selectedMonth . '-01');
                      $sq->whereNull('payroll_month')
                         ->whereYear('created_at', $date->year)
                         ->whereMonth('created_at', $date->month);
                  });
            });
        }
        
        // Apply custom search
        if ($this->tableSearch) {
            $search = $this->tableSearch;
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($employeeQuery) use ($search) {
                    $employeeQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('emp_id', 'like', "%{$search}%");
                });
            });
        }

        // In review mode, show only payrolls that are in a reviewable state.
        if ($this->payrollCategory === 'review') {
            $reviewStatuses = [
                PayrollStatus::SUBMITTED_TO_PROVIDER,
                PayrollStatus::CALCULATED,
                PayrollStatus::REBACK,
            ];

            if ($companyType === CompanyTypes::CLIENT) {
                $reviewStatuses = [
                    PayrollStatus::SUBMITTED_TO_CLIENT,
                    PayrollStatus::FINALIZED,
                    PayrollStatus::REBACK,
                ];
            }

            $query->whereIn('status', $reviewStatuses);
        }
        
        return $query;
    }

    protected function getReviewActionQuery()
    {
        return $this->getTableQuery()->whereIn('status', [
            PayrollStatus::SUBMITTED_TO_PROVIDER,
            PayrollStatus::CALCULATED,
            PayrollStatus::REBACK,
        ]);
    }

    protected function generateTaxInvoiceNumber(Payroll $payroll): string
    {
        $monthPart = str_replace('-', '', $payroll->payroll_month ?: now()->format('Y-m'));
        return 'TINV-' . $monthPart . '-' . str_pad((string) $payroll->id, 6, '0', STR_PAD_LEFT);
    }

    protected function hasReviewEligiblePayrolls(): bool
    {
        $reviewStatuses = [
            PayrollStatus::SUBMITTED_TO_PROVIDER,
            PayrollStatus::CALCULATED,
            PayrollStatus::SUBMITTED_TO_CLIENT,
            PayrollStatus::REBACK,
            PayrollStatus::FINALIZED,
        ];

        return $this->getTableQuery()
            ->whereIn('status', $reviewStatuses)
            ->exists();
    }

    public function getSelectedMonthYear(): string
    {
        if (!$this->selectedMonth) {
            return now()->format('F Y');
        }
        $date = Carbon::parse($this->selectedMonth . '-01');
        return $date->format('F Y');
    }

    public function previousMonth(): void
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $this->selectedMonth = $date->subMonth()->format('Y-m');
        $this->resetTable();
    }

    public function nextMonth(): void
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $this->selectedMonth = $date->addMonth()->format('Y-m');
        $this->resetTable();
    }

    public function getTotalEmployees(): int
    {
        return $this->getTableQuery()->count();
    }

    public function getTotalOvertime(): float
    {
        return (float) $this->getTableQuery()
            ->sum('overtime_amount') ?? 0.00;
    }

    public function getTotalWithoutOvertime(): float
    {
        // Total Without OT = Net Payment - Total Overtime
        return (float) ($this->getNetPayment() - $this->getTotalOvertime());
    }

    public function getNetPayment(): float
    {
        // Calculate net_payment using the accessor from Payroll model
        $payrolls = $this->getTableQuery()->get();
        return (float) $payrolls->sum('net_payment') ?? 0.00;
    }

    public function getEmployeePercentageChange(): string
    {
        // Calculate percentage change from previous month
        $current = $this->getTotalEmployees();
        $previous = $this->getPreviousMonthCount();
        
        if ($previous == 0) return '0.0';
        
        $change = (($current - $previous) / $previous) * 100;
        return number_format(abs($change), 1);
    }

    public function getOvertimePercentageChange(): string
    {
        $current = $this->getTotalOvertime();
        $previous = $this->getPreviousMonthOvertime();
        
        if ($previous == 0) return '0.0';
        
        $change = (($current - $previous) / $previous) * 100;
        return number_format(abs($change), 1);
    }

    public function getWithoutOvertimePercentageChange(): string
    {
        $current = $this->getTotalWithoutOvertime();
        $previous = $this->getPreviousMonthWithoutOvertime();
        
        if ($previous == 0) return '0.0';
        
        $change = (($current - $previous) / $previous) * 100;
        return number_format(abs($change), 1);
    }

    public function getNetPaymentPercentageChange(): string
    {
        $current = $this->getNetPayment();
        $previous = $this->getPreviousMonthNetPayment();
        
        if ($previous == 0) return '0.0';
        
        $change = (($current - $previous) / $previous) * 100;
        return number_format(abs($change), 1);
    }

    public function getPreviousMonthCount(): int
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $previousMonth = $date->copy()->subMonth();
        
        $query = PayrollResource::getEloquentQuery();
        $companyType = $this->getCurrentCompanyType();
        $companyId = $this->getCurrentCompanyId();

        if (! $companyId) {
            return 0;
        }
        
        if ($companyType === \App\Enums\CompanyTypes::PROVIDER) {
            $query->whereHas('employee', fn($q) => $q->where('company_id', $companyId));
            
            // Apply client company filter
            if ($this->clientCompany && $this->clientCompany !== 'all') {
                if ($this->clientCompany === 'in_house') {
                    $query->whereHas('employee', fn($q) =>
                        $q->whereDoesntHave('assigned', fn($sq) =>
                            $sq->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                        )
                    );
                } elseif ($this->clientCompany === 'no_payroll') {
                    $query->where('basic_salary', 0);
                } else {
                    $clientId = (int) $this->clientCompany;
                    $query->whereHas('employee.assigned', fn($q) =>
                        $q->where('employee_assigned.company_id', $clientId)
                          ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    );
                }
            }
        } else {
            $query->whereHas('employee.assigned', fn($q) => 
                                $q->where('employee_assigned.company_id', $companyId)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
            
            // Apply provider company filter
            if ($this->providerCompany && $this->providerCompany !== 'all') {
                $providerId = (int) $this->providerCompany;
                $query->whereHas('employee', fn($q) => $q->where('company_id', $providerId));
            }
        }
        
        return $query->where(function ($q) use ($previousMonth) {
                $q->where('payroll_month', $previousMonth->format('Y-m'))
                  ->orWhere(function ($sq) use ($previousMonth) {
                      $sq->whereNull('payroll_month')
                         ->whereYear('created_at', $previousMonth->year)
                         ->whereMonth('created_at', $previousMonth->month);
                  });
            })
                     ->count();
    }

    public function getPreviousMonthOvertime(): float
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $previousMonth = $date->copy()->subMonth();
        
        $query = PayrollResource::getEloquentQuery();
        $companyType = $this->getCurrentCompanyType();
        $companyId = $this->getCurrentCompanyId();

        if (! $companyId) {
            return 0.0;
        }
        
        if ($companyType === \App\Enums\CompanyTypes::PROVIDER) {
            $query->whereHas('employee', fn($q) => $q->where('company_id', $companyId));
            
            if ($this->clientCompany && $this->clientCompany !== 'all') {
                if ($this->clientCompany === 'in_house') {
                    $query->whereHas('employee', fn($q) =>
                        $q->whereDoesntHave('assigned', fn($sq) =>
                            $sq->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                        )
                    );
                } elseif ($this->clientCompany === 'no_payroll') {
                    $query->where('basic_salary', 0);
                } else {
                    $clientId = (int) $this->clientCompany;
                    $query->whereHas('employee.assigned', fn($q) =>
                        $q->where('employee_assigned.company_id', $clientId)
                          ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    );
                }
            }
        } else {
            $query->whereHas('employee.assigned', fn($q) => 
                                $q->where('employee_assigned.company_id', $companyId)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
            
            if ($this->providerCompany && $this->providerCompany !== 'all') {
                $providerId = (int) $this->providerCompany;
                $query->whereHas('employee', fn($q) => $q->where('company_id', $providerId));
            }
        }
        
        return (float) $query->where(function ($q) use ($previousMonth) {
                $q->where('payroll_month', $previousMonth->format('Y-m'))
                  ->orWhere(function ($sq) use ($previousMonth) {
                      $sq->whereNull('payroll_month')
                         ->whereYear('created_at', $previousMonth->year)
                         ->whereMonth('created_at', $previousMonth->month);
                  });
            })
                             ->sum('overtime_amount') ?? 0.00;
    }

    public function getPreviousMonthWithoutOvertime(): float
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $previousMonth = $date->copy()->subMonth();
        
        $query = PayrollResource::getEloquentQuery();
        $companyType = $this->getCurrentCompanyType();
        $companyId = $this->getCurrentCompanyId();

        if (! $companyId) {
            return 0.0;
        }
        
        if ($companyType === \App\Enums\CompanyTypes::PROVIDER) {
            $query->whereHas('employee', fn($q) => $q->where('company_id', $companyId));
            
            if ($this->clientCompany && $this->clientCompany !== 'all') {
                if ($this->clientCompany === 'in_house') {
                    $query->whereHas('employee', fn($q) =>
                        $q->whereDoesntHave('assigned', fn($sq) =>
                            $sq->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                        )
                    );
                } elseif ($this->clientCompany === 'no_payroll') {
                    $query->where('basic_salary', 0);
                } else {
                    $clientId = (int) $this->clientCompany;
                    $query->whereHas('employee.assigned', fn($q) =>
                        $q->where('employee_assigned.company_id', $clientId)
                          ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    );
                }
            }
        } else {
            $query->whereHas('employee.assigned', fn($q) => 
                                $q->where('employee_assigned.company_id', $companyId)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
            
            if ($this->providerCompany && $this->providerCompany !== 'all') {
                $providerId = (int) $this->providerCompany;
                $query->whereHas('employee', fn($q) => $q->where('company_id', $providerId));
            }
        }
        
        $payrolls = $query->where(function ($q) use ($previousMonth) {
                $q->where('payroll_month', $previousMonth->format('Y-m'))
                  ->orWhere(function ($sq) use ($previousMonth) {
                      $sq->whereNull('payroll_month')
                         ->whereYear('created_at', $previousMonth->year)
                         ->whereMonth('created_at', $previousMonth->month);
                  });
            })
            ->get();
        
        // Total Without OT = Net Payment - Overtime
        $netPayment = (float) $payrolls->sum('net_payment');
        $overtime = (float) $payrolls->sum('overtime_amount');
        return $netPayment - $overtime;
    }

    public function getPreviousMonthNetPayment(): float
    {
        $date = Carbon::parse($this->selectedMonth . '-01');
        $previousMonth = $date->copy()->subMonth();
        
        $query = PayrollResource::getEloquentQuery();
        $companyType = $this->getCurrentCompanyType();
        $companyId = $this->getCurrentCompanyId();

        if (! $companyId) {
            return 0.0;
        }
        
        if ($companyType === \App\Enums\CompanyTypes::PROVIDER) {
            $query->whereHas('employee', fn($q) => $q->where('company_id', $companyId));
            
            if ($this->clientCompany && $this->clientCompany !== 'all') {
                if ($this->clientCompany === 'in_house') {
                    $query->whereHas('employee', fn($q) =>
                        $q->whereDoesntHave('assigned', fn($sq) =>
                            $sq->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                        )
                    );
                } elseif ($this->clientCompany === 'no_payroll') {
                    $query->where('basic_salary', 0);
                } else {
                    $clientId = (int) $this->clientCompany;
                    $query->whereHas('employee.assigned', fn($q) =>
                        $q->where('employee_assigned.company_id', $clientId)
                          ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    );
                }
            }
        } else {
            $query->whereHas('employee.assigned', fn($q) => 
                                $q->where('employee_assigned.company_id', $companyId)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
            
            if ($this->providerCompany && $this->providerCompany !== 'all') {
                $providerId = (int) $this->providerCompany;
                $query->whereHas('employee', fn($q) => $q->where('company_id', $providerId));
            }
        }
        
        $payrolls = $query->where(function ($q) use ($previousMonth) {
                $q->where('payroll_month', $previousMonth->format('Y-m'))
                  ->orWhere(function ($sq) use ($previousMonth) {
                      $sq->whereNull('payroll_month')
                         ->whereYear('created_at', $previousMonth->year)
                         ->whereMonth('created_at', $previousMonth->month);
                  });
            })
                          ->get();
        
        return (float) $payrolls->sum('net_payment') ?? 0.00;
    }

    public function getLastUpdateDate(): string
    {
        $latest = $this->getTableQuery()->latest('updated_at')->first();
        if ($latest) {
            return $latest->updated_at->format('F d, Y');
        }
        return now()->format('F d, Y');
    }

    public function calculatePayroll(): void
    {
        $companyType = $this->getCurrentCompanyType();
        $companyId = $this->getCurrentCompanyId();
        $date = Carbon::parse($this->selectedMonth . '-01');

        if (! $companyId || ! $companyType) {
            Notification::make()
                ->title('تعذر تحديد الشركة الحالية')
                ->danger()
                ->send();
            return;
        }
        
        // Get employees based on company type and selected client company
        if ($companyType === \App\Enums\CompanyTypes::PROVIDER) {
            $employeesQuery = \App\Models\Employee::where('company_id', $companyId);
            
            // If a specific client company is selected, only get employees assigned to that company
            if ($this->clientCompany && $this->clientCompany !== 'all') {
                if ($this->clientCompany === 'in_house') {
                    // In-House: employees NOT assigned to any client
                    $employeesQuery->whereDoesntHave('assigned', fn($q) =>
                        $q->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    );
                } elseif ($this->clientCompany === 'no_payroll') {
                    // No Payroll: employees without payroll for this month
                    $employeesQuery->whereDoesntHave('payrolls', fn($q) =>
                        $q->where('payroll_month', $date->format('Y-m'))
                    );
                } else {
                    $clientId = (int) $this->clientCompany;
                    $employeesQuery->whereHas('assigned', fn($q) =>
                        $q->where('employee_assigned.company_id', $clientId)
                          ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
                    );
                }
            }
            
            $employees = $employeesQuery->get();
        } else {
            $employeesQuery = \App\Models\Employee::whereHas('assigned', fn($q) => 
                                $q->where('employee_assigned.company_id', $companyId)
                  ->where('employee_assigned.status', \App\Enums\EmployeeAssignedStatus::APPROVED)
            );
            
            // If a specific provider company is selected, only get employees from that provider
            if ($this->providerCompany && $this->providerCompany !== 'all') {
                $providerId = (int) $this->providerCompany;
                $employeesQuery->where('company_id', $providerId);
            }
            
            $employees = $employeesQuery->get();
        }
        
        if ($employees->isEmpty()) {
            Notification::make()
                ->title('No employees found')
                ->warning()
                ->send();
            return;
        }
        
        $created = 0;
        $existing = 0;
        $updated = 0;
        $missingData = [];
        
        foreach ($employees as $employee) {
            // Check if payroll already exists for this employee and month (own records only)
            $existingPayroll = Payroll::where('employee_id', $employee->id)
                ->where('company_id', $companyId)
                ->where('payroll_month', $this->selectedMonth)
                ->first();
            
            // Check if employee has a template payroll (any month) with filled data
            // First check own records, then fallback to provider's records (for CLIENT)
            $templatePayroll = Payroll::where('employee_id', $employee->id)
                ->where('company_id', $companyId)
                ->where('basic_salary', '>', 0)
                ->latest()
                ->first();
            
            // CLIENT fallback: if no own template, use PROVIDER's payroll as template
            if (!$templatePayroll && $companyType === \App\Enums\CompanyTypes::CLIENT) {
                $templatePayroll = Payroll::where('employee_id', $employee->id)
                    ->where('company_id', $employee->company_id) // PROVIDER owns the employee
                    ->where('basic_salary', '>', 0)
                    ->latest()
                    ->first();
            }
            
            if ($existingPayroll) {
                // If payroll exists with data, skip
                if ($existingPayroll->basic_salary > 0) {
                    $existing++;
                    continue;
                }
                
                // If payroll exists but empty, check if we have template
                if (!$templatePayroll) {
                    // No template found - leave the empty record for manual entry
                    $existing++;
                    continue;
                }
                
                // Update existing empty payroll with template data
                $existingPayroll->update([
                    'basic_salary' => $templatePayroll->basic_salary,
                    'housing_allowance' => $templatePayroll->housing_allowance,
                    'transportation_allowance' => $templatePayroll->transportation_allowance,
                    'food_allowance' => $templatePayroll->food_allowance,
                    'other_allowance' => $templatePayroll->other_allowance,
                    'fees' => $templatePayroll->fees,
                    'total_package' => $templatePayroll->total_package,
                ]);

                // Sync OT, additions, timesheet, deductions from current entries
                Payroll::syncFromEntries($employee->id, $companyId, $this->selectedMonth);
                $updated++;
                continue;
            }
            
            if (!$templatePayroll) {
                // No template found - create empty DRAFT payroll so provider can fill manually
                Payroll::create([
                    'employee_id' => $employee->id,
                    'company_id' => $companyId,
                    'payroll_month' => $this->selectedMonth,
                    'status' => \App\Enums\PayrollStatus::DRAFT,
                    'basic_salary' => 0,
                    'housing_allowance' => 0,
                    'transportation_allowance' => 0,
                    'food_allowance' => 0,
                    'other_allowance' => 0,
                    'fees' => 0,
                    'total_package' => 0,
                    'added_days' => 0,
                    'overtime_hours' => 0,
                    'overtime_amount' => 0,
                    'added_days_amount' => 0,
                    'other_additions' => 0,
                    'absence_days' => 0,
                    'absence_unpaid_leave_deduction' => 0,
                    'food_subscription_deduction' => 0,
                    'other_deduction' => 0,
                    'created_at' => $date,
                    'updated_at' => now(),
                ]);
                // Even with no salary basis, sync any existing OT/additions entries
                Payroll::syncFromEntries($employee->id, $companyId, $this->selectedMonth);
                $created++;
                continue;
            }
            
            // Create new payroll using template data
            $payroll = Payroll::create([
                'employee_id' => $employee->id,
                'company_id' => $companyId,
                'payroll_month' => $this->selectedMonth,
                'status' => \App\Enums\PayrollStatus::DRAFT,
                'basic_salary' => $templatePayroll->basic_salary,
                'housing_allowance' => $templatePayroll->housing_allowance,
                'transportation_allowance' => $templatePayroll->transportation_allowance,
                'food_allowance' => $templatePayroll->food_allowance,
                'other_allowance' => $templatePayroll->other_allowance,
                'fees' => $templatePayroll->fees,
                'total_package' => $templatePayroll->total_package,
                'added_days' => 0,
                'added_days_amount' => 0,
                'created_at' => $date,
                'updated_at' => now(),
            ]);

            // Sync OT, additions, timesheet, deductions from current entries
            Payroll::syncFromEntries($employee->id, $companyId, $this->selectedMonth);

            $created++;
        }
        
        $this->resetTable();
        
        // Show warning for employees with missing payroll data
        if (!empty($missingData)) {
            $names = implode('، ', $missingData);
            Notification::make()
                ->title('لا يمكن احتساب الراتب - بيانات ناقصة')
                ->body("الموظفون التالية أسماؤهم لا تتوفر لديهم بيانات رواتب: {$names}")
                ->danger()
                ->persistent()
                ->send();
        }
        
        if ($created > 0 || $updated > 0) {
            $message = [];
            if ($created > 0) $message[] = "تم إنشاء: {$created}";
            if ($updated > 0) $message[] = "تم تحديث: {$updated}";
            if ($existing > 0) $message[] = "موجود مسبقاً: {$existing}";
            
            Notification::make()
                ->title('تم احتساب الرواتب')
                ->body(implode(' | ', $message))
                ->success()
                ->send();
        } elseif (empty($missingData)) {
            Notification::make()
                ->title('جميع الرواتب موجودة مسبقاً')
                ->body("جميع الموظفين لديهم كشوف رواتب لهذا الشهر")
                ->info()
                ->send();
        }
    }

    public function toggleSalaryImport(): void
    {
        $this->showSalaryImport = !$this->showSalaryImport;
        $this->salaryFile = null;
    }

    /**
     * Get a salary field from normalized row data trying multiple possible column names.
     */
    protected function getSalaryField(array $normalized, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (isset($normalized[$key]) && $normalized[$key] !== '' && $normalized[$key] !== null) {
                return $normalized[$key];
            }
        }
        return $default;
    }

    public function importSalaries(): void
    {
        if ($this->payrollCategory !== 'contracted') {
            Notification::make()
                ->title('الاستيراد متاح فقط في Contracted Payroll')
                ->warning()
                ->send();
            return;
        }

        if (!$this->salaryFile) {
            Notification::make()
                ->title('يرجى رفع ملف رواتب')
                ->warning()
                ->send();
            return;
        }

        $companyId = $this->getCurrentCompanyId();

        if (! $companyId) {
            Notification::make()
                ->title('تعذر تحديد الشركة الحالية')
                ->danger()
                ->send();
            return;
        }

        try {
            $extension = strtolower((string) $this->salaryFile->getClientOriginalExtension());
            if (!in_array($extension, ['csv', 'xlsx', 'xls'], true)) {
                Notification::make()
                    ->title('صيغة الملف غير مدعومة')
                    ->body('الملفات المسموحة: CSV, XLSX, XLS')
                    ->warning()
                    ->send();
                return;
            }

            // Get file path from Livewire temporary upload
            $filePath = $this->salaryFile->getRealPath();
            $parsed = $this->readSalarySpreadsheet($filePath);
            $this->validateStrictPayrollHeaders($parsed['headers']);
            $rows = $parsed['rows'];

            if (empty($rows)) {
                Notification::make()
                    ->title('الملف فارغ')
                    ->warning()
                    ->send();
                return;
            }

            $employeeBaseQuery = $this->getImportEmployeesQuery();

            $updated = 0;
            $created = 0;
            $skipped = 0;
            $errors = [];

            foreach ($rows as $rowNum => $normalized) {

                try {
                    // Strict template mapping: Emp.ID and Iqama No
                    $empId = $this->getSalaryField($normalized, [
                        'emp.id',
                    ]);

                    $iqamaNo = $this->getSalaryField($normalized, [
                        'iqama no',
                    ]);

                    $employee = null;

                    if ($empId) {
                        $employee = (clone $employeeBaseQuery)
                            ->where('emp_id', $empId)
                            ->first();
                    }

                    if (!$employee && $iqamaNo) {
                        $employee = (clone $employeeBaseQuery)
                            ->where('iqama_no', $iqamaNo)
                            ->first();
                    }

                    if (!$employee) {
                        $identifier = $empId ?: ($iqamaNo ?: "Row {$rowNum}");
                        $errors[] = "Row {$rowNum}: Employee not found ({$identifier})";
                        $skipped++;
                        continue;
                    }

                    // Payroll fields from strict template columns only.
                    $basicSalary = $this->getStrictSalaryNumericField($normalized, 'basic salary');
                    $housingAllowance = $this->getStrictSalaryNumericField($normalized, 'housing allowance');
                    $transportationAllowance = $this->getStrictSalaryNumericField($normalized, 'transportation allowance');
                    $foodAllowance = $this->getStrictSalaryNumericField($normalized, 'food allowance');
                    $otherAllowance = $this->getStrictSalaryNumericField($normalized, 'other allowance');
                    $fees = $this->getStrictSalaryNumericField($normalized, 'fees');

                    if (!$basicSalary || (float) $basicSalary <= 0) {
                        $errors[] = "Row {$rowNum}: Missing or zero Basic Salary for {$employee->name}";
                        $skipped++;
                        continue;
                    }

                    // Build salary data
                    $salaryData = [
                        'basic_salary' => (float) ($basicSalary ?? 0),
                        'housing_allowance' => (float) ($housingAllowance ?? 0),
                        'transportation_allowance' => (float) ($transportationAllowance ?? 0),
                        'food_allowance' => (float) ($foodAllowance ?? 0),
                        'other_allowance' => (float) ($otherAllowance ?? 0),
                        'fees' => (float) ($fees ?? 0),
                    ];

                    // Calculate total_package (salary package only, without monthly fees)
                    $salaryData['total_package'] = $salaryData['basic_salary']
                        + $salaryData['housing_allowance']
                        + $salaryData['transportation_allowance']
                        + $salaryData['food_allowance']
                        + $salaryData['other_allowance'];

                    // Find or create payroll for this employee + month
                    $payroll = Payroll::where('employee_id', $employee->id)
                        ->where('company_id', $companyId)
                        ->where('payroll_month', $this->selectedMonth)
                        ->first();

                    if ($payroll) {
                        $payroll->update($salaryData);
                        $updated++;
                    } else {
                        Payroll::create(array_merge($salaryData, [
                            'employee_id' => $employee->id,
                            'company_id' => $companyId,
                            'payroll_month' => $this->selectedMonth,
                            'status' => \App\Enums\PayrollStatus::DRAFT,
                            'added_days' => 0,
                            'overtime_hours' => 0,
                            'overtime_amount' => 0,
                            'added_days_amount' => 0,
                            'other_additions' => 0,
                            'absence_days' => 0,
                            'absence_unpaid_leave_deduction' => 0,
                            'food_subscription_deduction' => 0,
                            'other_deduction' => 0,
                        ]));
                        $created++;
                    }

                    // Optional: update employee hiring date/location if provided in the import file.
                    $hiringDate = $this->getSalaryField($normalized, [
                        'hiring date',
                    ]);

                    $location = $this->getSalaryField($normalized, [
                        'location',
                        'work location',
                        'site',
                    ]);

                    $shouldSaveEmployee = false;

                    if ($hiringDate) {
                        try {
                            $parsed = Carbon::parse((string) $hiringDate);
                            $employee->hire_date = $parsed->format('Y-m-d');
                            $shouldSaveEmployee = true;
                        } catch (\Throwable $e) {
                            // Ignore invalid date values from import file.
                        }
                    }

                    if (is_string($location) && trim($location) !== '') {
                        $employee->location = trim($location);
                        $shouldSaveEmployee = true;
                    }

                    if ($shouldSaveEmployee) {
                        $employee->save();
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNum}: " . $e->getMessage();
                    $skipped++;
                }
            }

            // Clean up
            $this->salaryFile = null;
            $this->showSalaryImport = false;
            $this->resetTable();

            // Show results
            $message = [];
            if ($created > 0) $message[] = "تم إنشاء: {$created}";
            if ($updated > 0) $message[] = "تم تحديث: {$updated}";
            if ($skipped > 0) $message[] = "تم تخطي: {$skipped}";

            Notification::make()
                ->title('تم رفع الرواتب بنجاح')
                ->body(implode(' | ', $message))
                ->success()
                ->send();

            if (!empty($errors)) {
                Notification::make()
                    ->title('بعض الصفوف فيها أخطاء')
                    ->body(implode("\n", array_slice($errors, 0, 10)))
                    ->warning()
                    ->persistent()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطأ في رفع الملف')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Employees available for salary import, aligned with the current page filters.
     */
    protected function getImportEmployeesQuery(): Builder
    {
        $companyType = $this->getCurrentCompanyType();
        $companyId = $this->getCurrentCompanyId();
        $query = Employee::query();

        if (! $companyId) {
            return $query->whereRaw('1 = 0');
        }

        if ($companyType === CompanyTypes::PROVIDER) {
            $query->where('company_id', $companyId);

            if ($this->clientCompany && $this->clientCompany !== 'all') {
                if ($this->clientCompany === 'in_house') {
                    $query->whereDoesntHave('assigned', fn($sq) =>
                        $sq->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
                    );
                } elseif ($this->clientCompany !== 'no_payroll') {
                    $clientId = (int) $this->clientCompany;
                    $query->whereHas('assigned', fn($sq) =>
                        $sq->where('employee_assigned.company_id', $clientId)
                           ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
                    );
                }
            }

            return $query;
        }

        $query->whereHas('assigned', fn($sq) =>
            $sq->where('employee_assigned.company_id', $companyId)
               ->where('employee_assigned.status', EmployeeAssignedStatus::APPROVED)
        );

        if ($this->providerCompany && $this->providerCompany !== 'all') {
            $providerId = (int) $this->providerCompany;
            $query->where('company_id', $providerId);
        }

        return $query;
    }

    /**
     * Strict template headers from opernize Form.xlsx.
     */
    protected function getStrictPayrollTemplateHeaders(): array
    {
        return [
            'emp.id',
            'name',
            'nationality',
            'iqama no',
            'hiring date',
            'title',
            'department',
            'basic salary',
            'housing allowance',
            'transportation allowance',
            'food allowance',
            'other allowance',
            'fees',
        ];
    }

    protected function getStrictPayrollTemplateHeadersWithLocation(): array
    {
        return [
            'emp.id',
            'name',
            'nationality',
            'iqama no',
            'hiring date',
            'title',
            'department',
            'location',
            'basic salary',
            'housing allowance',
            'transportation allowance',
            'food allowance',
            'other allowance',
            'fees',
        ];
    }

    /**
     * Fail import when uploaded headers differ from template headers.
     */
    protected function validateStrictPayrollHeaders(array $headers): void
    {
        $acceptedTemplates = [
            $this->getStrictPayrollTemplateHeaders(),
            $this->getStrictPayrollTemplateHeadersWithLocation(),
        ];

        foreach ($acceptedTemplates as $template) {
            if ($headers === $template) {
                return;
            }
        }

        throw new \RuntimeException(
            'صيغة الأعمدة غير مطابقة للقالب opernize Form.xlsx. '
            . 'الترتيب المطلوب (بدون موقع): ' . implode(' | ', $this->getStrictPayrollTemplateHeaders())
            . ' || أو (مع موقع): ' . implode(' | ', $this->getStrictPayrollTemplateHeadersWithLocation())
        );
    }

    /**
     * Read CSV/XLSX salary file with header list and normalized row data.
     */
    protected function readSalarySpreadsheet(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $headers = [];
        $normalizedHeaders = [];
        $rows = [];

        foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            $colIndex = 0;

            foreach ($cellIterator as $cell) {
                $value = $cell->getValue();

                if ($rowIndex === 1) {
                    $header = strtolower(trim((string) $value));
                    $headers[$colIndex] = $header;
                    $normalizedHeaders[] = $header;
                } else {
                    if (!isset($headers[$colIndex]) || $headers[$colIndex] === '') {
                        $colIndex++;
                        continue;
                    }

                    $key = $headers[$colIndex];
                    $normalized = $this->normalizeHeaderKey($key);
                    $stringValue = is_string($value) ? trim($value) : $value;

                    foreach ($normalized as $variant) {
                        $rowData[$variant] = $stringValue;
                    }
                }

                $colIndex++;
            }

            if ($rowIndex > 1 && !empty(array_filter($rowData, fn($v) => $v !== null && $v !== ''))) {
                $rows[$rowIndex] = $rowData;
            }
        }

        return [
            'headers' => $normalizedHeaders,
            'rows' => $rows,
        ];
    }

    /**
     * Build common normalized variants for a header key.
     */
    protected function normalizeHeaderKey(string $header): array
    {
        $clean = strtolower(trim($header));
        $variants = [$clean];

        $underscored = str_replace(' ', '_', $clean);
        $nospace = str_replace(['_', ' '], '', $clean);
        $nodot = str_replace(['.', '_', ' '], '', $clean);

        foreach ([$underscored, $nospace, $nodot] as $variant) {
            if ($variant !== '') {
                $variants[] = $variant;
            }
        }

        return array_values(array_unique($variants));
    }

    /**
     * Parse numeric salary values from spreadsheet cells.
     */
    protected function getSalaryNumericField(array $normalized, array $keys): ?float
    {
        $raw = $this->getSalaryField($normalized, $keys);
        if ($raw === null || $raw === '') {
            return null;
        }

        $cleaned = preg_replace('/[^\d.\-]/', '', (string) $raw);
        if ($cleaned === null || $cleaned === '' || $cleaned === '-') {
            return null;
        }

        return (float) $cleaned;
    }

    /**
     * Strict numeric parser for template numeric columns.
     */
    protected function getStrictSalaryNumericField(array $normalized, string $key): ?float
    {
        $raw = $this->getSalaryField($normalized, [$key]);
        if ($raw === null || $raw === '') {
            return null;
        }

        $clean = str_replace([',', ' '], '', (string) $raw);
        if (!is_numeric($clean)) {
            throw new \InvalidArgumentException("Invalid numeric value in column '{$key}': {$raw}");
        }

        return (float) $clean;
    }
}
