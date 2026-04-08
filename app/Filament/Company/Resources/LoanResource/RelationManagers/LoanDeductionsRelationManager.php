<?php

namespace App\Filament\Company\Resources\LoanResource\RelationManagers;

use App\Enums\DeductionStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LoanDeductionsRelationManager extends RelationManager
{
    protected static string $relationship = 'deductions';

    protected static ?string $title = 'أقساط القرض المخصومة';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payroll_month')
                    ->label('شهر الراتب')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ الخصم')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' ريال'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => DeductionStatus::getTranslatedEnum()[$state] ?? $state)
                    ->color(fn ($state) => DeductionStatus::getColors()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date('Y-m-d'),
            ])
            ->defaultSort('payroll_month', 'desc')
            ->paginated(false);
    }
}
