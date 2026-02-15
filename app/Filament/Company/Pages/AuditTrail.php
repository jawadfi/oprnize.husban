<?php

namespace App\Filament\Company\Pages;

use App\Enums\CompanyTypes;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class AuditTrail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'سجل التدقيق / Audit Trail';

    protected static ?string $title = 'سجل التدقيق / Audit Trail';

    protected static ?string $slug = 'audit-trail';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.company.pages.audit-trail';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        // Only company admins and non-branch-managers can access
        if ($user instanceof Company) {
            return true;
        }

        if ($user instanceof User) {
            return !$user->isBranchManager();
        }

        return false;
    }

    protected function getTableQuery(): Builder
    {
        $user = Filament::auth()->user();

        if ($user instanceof Company) {
            $companyId = $user->id;
        } elseif ($user instanceof User) {
            $companyId = $user->company_id;
        } else {
            $companyId = 0;
        }

        // Show activities related to this company's models
        return Activity::query()
            ->where(function ($query) use ($companyId, $user) {
                // Activities caused by this company or its users
                $query->where(function ($q) use ($companyId, $user) {
                    $q->where('causer_type', Company::class)->where('causer_id', $companyId);
                })
                ->orWhere(function ($q) use ($companyId) {
                    $q->where('causer_type', User::class)
                      ->whereIn('causer_id', User::where('company_id', $companyId)->pluck('id'));
                });
            });
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('created_at')
                ->label('التاريخ / Date')
                ->dateTime('Y-m-d H:i:s')
                ->sortable(),

            Tables\Columns\TextColumn::make('log_name')
                ->label('النوع / Type')
                ->badge()
                ->color(fn ($state) => match ($state) {
                    'branch_entry' => 'info',
                    'deduction' => 'danger',
                    'payroll' => 'success',
                    default => 'gray',
                })
                ->formatStateUsing(fn ($state) => match ($state) {
                    'branch_entry' => 'إدخال فرع',
                    'deduction' => 'خصم',
                    'payroll' => 'مسير رواتب',
                    default => $state,
                }),

            Tables\Columns\TextColumn::make('description')
                ->label('الوصف / Description')
                ->wrap()
                ->limit(50),

            Tables\Columns\TextColumn::make('event')
                ->label('الحدث / Event')
                ->badge()
                ->color(fn ($state) => match ($state) {
                    'created' => 'success',
                    'updated' => 'warning',
                    'deleted' => 'danger',
                    default => 'gray',
                })
                ->formatStateUsing(fn ($state) => match ($state) {
                    'created' => 'إنشاء',
                    'updated' => 'تعديل',
                    'deleted' => 'حذف',
                    default => $state,
                }),

            Tables\Columns\TextColumn::make('causer_type')
                ->label('بواسطة / By')
                ->formatStateUsing(function ($state, $record) {
                    if (!$record->causer) return '-';
                    if ($state === Company::class) {
                        return $record->causer->name ?? 'شركة';
                    }
                    if ($state === User::class) {
                        return $record->causer->name ?? 'مستخدم';
                    }
                    return $state;
                }),

            Tables\Columns\TextColumn::make('subject_type')
                ->label('العنصر / Subject')
                ->formatStateUsing(fn ($state) => match ($state) {
                    'App\\Models\\BranchEntry' => 'إدخال فرع',
                    'App\\Models\\Deduction' => 'خصم',
                    'App\\Models\\Payroll' => 'مسير رواتب',
                    default => class_basename($state ?? ''),
                }),

            Tables\Columns\TextColumn::make('subject_id')
                ->label('رقم العنصر / ID')
                ->alignCenter(),

            Tables\Columns\TextColumn::make('properties')
                ->label('التغييرات / Changes')
                ->formatStateUsing(function ($state) {
                    if (!$state) return '-';
                    $props = is_string($state) ? json_decode($state, true) : $state;
                    if (empty($props)) return '-';

                    $parts = [];
                    if (isset($props['old']) && isset($props['attributes'])) {
                        foreach ($props['attributes'] as $key => $newVal) {
                            $oldVal = $props['old'][$key] ?? '-';
                            $parts[] = "{$key}: {$oldVal} → {$newVal}";
                        }
                    } elseif (isset($props['attributes'])) {
                        foreach ($props['attributes'] as $key => $val) {
                            $parts[] = "{$key}: {$val}";
                        }
                    }
                    return implode(' | ', $parts) ?: '-';
                })
                ->wrap()
                ->limit(100),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('log_name')
                ->label('النوع / Type')
                ->options([
                    'branch_entry' => 'إدخال فرع',
                    'deduction' => 'خصم',
                    'payroll' => 'مسير رواتب',
                ]),

            Tables\Filters\SelectFilter::make('event')
                ->label('الحدث / Event')
                ->options([
                    'created' => 'إنشاء',
                    'updated' => 'تعديل',
                    'deleted' => 'حذف',
                ]),
        ];
    }

    protected function getTableDefaultSort(): ?string
    {
        return 'created_at';
    }

    protected function getTableDefaultSortDirection(): ?string
    {
        return 'desc';
    }
}
