<?php


namespace App\Helpers\Filament\Components;


use App\Filament\Common\Resources\StudentResource;
use App\Helpers\Helpers;
use App\Models\Student;
use App\Models\Teacher;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;
use Modules\University\App\Facades\StudentService;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Filament\Forms;
use Filament\Forms\Form;
class FilamentComponents
{
    public static function TeacherSelect($name='teacher_id',$label='المدّرس'): Forms\Components\Select
    {
        return Forms\Components\Select::make($name)
            ->label($label)
            ->preload()
            ->required()
            ->searchable()
            ->options(
                Teacher::query()
                    ->relatedToCourse(Helpers::get_current_course())
                    ->pluck('name','id')
            )->createOptionForm([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('name')->required()
                        ->label('الاسم'),
                    Forms\Components\TextInput::make('address')->required()
                        ->label('العنوان'),
                    PhoneInput::make('phone')->label('رقم الهاتف')->required()
                        ->defaultCountry('SY')
                        ->initialCountry('SY')
                        ->inputNumberFormat(\Ysfkaya\FilamentPhoneInput\PhoneInputNumberType::INTERNATIONAL)
                        ->displayNumberFormat(\Ysfkaya\FilamentPhoneInput\PhoneInputNumberType::INTERNATIONAL),

                ])
            ])->createOptionUsing(function (array $data): int {
                $data['course_id'] = Helpers::get_current_course()->id;
                /** @var Teacher $teacher */
                $teacher = Teacher::create($data);
                return  $teacher->getKey();
            });
    }
    public static function StudentSelect($name='student_id'): Select
    {
        return Select::make($name)
            ->preload()
            ->options(Student::latest()->limit(50)->select(
                DB::raw("CONCAT(`first_name`,' ',`middle_name`,' ',`last_name`) AS name"), 'id')
                ->pluck('name', 'id'))
            ->optionsLimit(50)
            ->live()
            ->label('الطالب')
            ->searchable()
            ->required()
            ->getSearchResultsUsing(fn(string $search): array => Student::query()
                ->where(function ($query) use ($search) {
                    return $query->where(DB::raw("CONCAT(`first_name`,' ',`middle_name`,' ',`last_name`)"), 'like', "%$search%")
                        ->orWhere('username', 'like', "%$search%");
                })
                ->limit(50)
                ->select(
                    DB::raw("CONCAT(`first_name`,' ',`middle_name`,' ',`last_name`) AS name"), 'id')
                ->pluck('name', 'id')
                ->toArray())
            ->getOptionLabelUsing(fn($value): ?string => Student::find($value)?->name)
            ->createOptionForm(StudentResource::getForm(Helpers::get_current_project()))->createOptionUsing(function (array $data): int {

                $id = \App\Models\Student::create($data)->getKey();

                return $id;
            });
    }
}
