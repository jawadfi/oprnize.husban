<?php

namespace App\Filament\Company\Imports;

use App\Filament\Imports\Course;
use App\Helpers\Helpers;
use App\Models\Employee;
use App\Models\Teacher;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Carbon;

class EmployeeImporter extends Importer
{
    protected static ?string $model = Employee::class;

    public function import(array $row): void
    {
        $row['company_id'] = $this->options['company_id'];
        if (isset($row['hire_date'])) {
            try {
                $row['hire_date'] = Carbon::make($row['hire_date'])->format('Y-m-d');
            } catch (\Exception $exception) {

            }
        }
        Employee::query()->updateOrCreate([
            'identity_number' => $row['identity_number'],
        ], $row);
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping(),
            ImportColumn::make('job_title')
                ->requiredMapping(),
            ImportColumn::make('department'),
            ImportColumn::make('location'),
            ImportColumn::make('iqama_no'),
            ImportColumn::make('hire_date'),
            ImportColumn::make('identity_number')
                ->requiredMapping(),
            ImportColumn::make('nationality')
                ->requiredMapping(),
            ImportColumn::make('email'),
        ];
    }

    public function resolveRecord(): ?Employee
    {
        return null;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your course import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
