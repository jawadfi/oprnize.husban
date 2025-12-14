<?php

namespace App\Filament\Company\Resources\AssignedEmployeeResource\Pages;

use App\Enums\EmployeeAssignedStatus;
use App\Filament\Company\Resources\AssignedEmployeeResource;
use App\Models\Company;
use App\Models\Employee;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageAssignedEmployees extends ManageRecords
{
    protected static string $resource = AssignedEmployeeResource::class;

    public function getTabs(): array
    {
        $tabs = [];
        $used_employee = Employee::whereHas('assigned', function ($query) {
            return $query->where('employee_assigned.company_id', Filament::auth()->id())
                ->where('employee_assigned.status',EmployeeAssignedStatus::APPROVED)
                ->whereDate('employee_assigned.start_date','<=',now())
                ;
        });

        $used_employee_collection = $used_employee->get();

        $company_ids = $used_employee->distinct()->pluck('company_id')->toArray();
        $companies = Company::find($company_ids);
        foreach ($companies as $company) {
            $tabs[$company->name] = Tab::make()
                ->badge(fn() => $used_employee_collection->where('company_id', $company->id)->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('company_id', $company->id))
            ;
        }
        return $tabs;

    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
