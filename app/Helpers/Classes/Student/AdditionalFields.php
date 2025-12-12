<?php


namespace App\Helpers\Classes\Student;


use App\Enums\EducationStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Helpers\Classes\Enum\FieldType;
use App\Facades\StudentService;
use App\Helpers\Helpers;
use App\Models\Project;
use App\Models\University;
use App\Models\UniversitySpecialization;
use App\Models\Work;
use App\Rules\ValidCountry;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class AdditionalFields
{

    public function __construct()
    {
    }

    const SCHOOL = 'school';
    const SCHOOL_CLASS = 'class';
    const UNIVERSITY = 'university';
    const UNIVERSITY_SPECIALIZATION = 'university_specialization';
    const WORK = 'work';
    const OUTSIDE_COURSE = 'outside_courses';
    const NUMBER_FAMILY = 'number_family';
    const FATHER_WORK = 'father_work';
    const EMAIL = 'email';
    const FACEBOOK = 'facebook';
    const FATHER_PHONE = 'father_phone';
    const MOTHER_PHONE = 'mother_phone';
    const LANDLINE = 'landline';
    const NATIONALITY = 'nationality';
    const COUNTRY = 'country';

    const FIRST_NAME = 'first_name';
    const MIDDLE_NAME = 'middle_name';
    const LAST_NAME = 'last_name';
    const BIRTH_DATE = 'birth_date';
    const PHONE = 'phone';
    const ADDRESS = 'address';
    const MARTIAL_STATUS = 'marital_status';
    const GENDER = 'gender';
    const WHATSAPP = 'whatsapp';
    const EDUCATION_STATUS = 'education_status';
    const REQUIRED_LEARNING_TOPICS = 'required_learning_topics';


    private static function key_value($list, $with_key = false, $key_name = null): array
    {
        return collect(array_filter($list))->map(function ($record, $key) use ($with_key, $key_name) {
            if ($with_key)
                return [
                    'value' => $key,
                    'key' => $key_name ? $record[$key_name] : $record
                ];
            else
                return [
                    'key' => $record,
                    'value' => $record
                ];
        })->values()->toArray();
    }

    public static function getFilamentComponent($key, Project $project)
    {
        if(!in_array($key, $project->required_fields) || !array_key_exists($key,self::getFilamentComponents()))
            return (new Forms\Components\Component())->hidden();

        /** @var Forms\Components\Component $component */
        return self::getFilamentComponents()[$key];
    }

    public static function isVisableComponent($key, Project $project)
    {
        return in_array($key, $project->required_fields);
    }

    public static function getFilamentComponents(): array
    {
        return [
            self::REQUIRED_LEARNING_TOPICS=>Textarea::make('required_learning_topics')->rows(5)->columnSpanFull()->label('ماذا تحتاج أن تتعلم في هذه الدورة؟'),
            self::FIRST_NAME => TextInput::make('first_name')->label('الاسم')->required(),
            self::MIDDLE_NAME => TextInput::make('middle_name')->label('اسم الأب')->required(),
            self::LAST_NAME => TextInput::make('last_name')->label('الكنية')->required(),
            self::BIRTH_DATE => Forms\Components\Grid::make()->columns([
                'xs' => 3,
                'sm' => 3,
                'md' => 3,
                'lg' => 3,
                'default' => 3,
            ])->schema([
                Forms\Components\Placeholder::make('birth')->label(
                    new HtmlString(' تاريخ الميلاد<sup class="text-danger-600 dark:text-danger-400 font-medium">*</sup>')
                )->columnSpanFull(),
                TextInput::make('day')->formatStateUsing(fn($state,$record)=>$state??($record?Carbon::make($record->birth_date)->day:null))->placeholder('يوم')->hiddenLabel()->numeric()->required()->minValue(1)->maxValue(30)->maxLength(2),
                Select::make('month')->formatStateUsing(fn($state,$record)=>$state??($record?Carbon::make($record->birth_date)->month:null))->placeholder('شهر')->label('الشهر')->options([
                    1 => "كانون الثاني",
                    2 => "شباط",
                    3 => "آذار",
                    4 => "نيسان",
                    5 => "أيار",
                    6 => "حزيران",
                    7 => "تموز",
                    8 => "آب",
                    9 => "أيلول",
                    10 => "تشرين الأول",
                    11 => "تشرين الثاني",
                    12 => "كانون الأول",
                ])->required()->hiddenLabel(),
                TextInput::make('year')->formatStateUsing(fn($state,$record)=>$state??($record?Carbon::make($record->birth_date)->year:null))->hiddenLabel()->placeholder('سنة')->numeric()->required()->maxValue(9999)->minValue(1000)->maxLength(4),
                Forms\Components\Hidden::make('birth_date')->dehydrateStateUsing(fn(Get $get) => Carbon::make($get('year') . '-' . $get('month') . '-' . $get('day'))->format('Y-m-d'))
            ])->columnSpanFull(),

            self::PHONE => PhoneInput::make('phone')->label('رقم الجوال')
                ->defaultCountry('SY')
                ->initialCountry('SY')
                ->required()
                ->live(true)
                ->dehydrateStateUsing(fn(string $state): string => str_replace(' ', '', trim($state)))
                ->afterStateUpdated(function ($state, Forms\Set $set, Get $get) {
                    $set('whatsapp', $state);
                })
                ->inputNumberFormat(\Ysfkaya\FilamentPhoneInput\PhoneInputNumberType::INTERNATIONAL)
                ->displayNumberFormat(\Ysfkaya\FilamentPhoneInput\PhoneInputNumberType::INTERNATIONAL),
            self::ADDRESS => TextInput::make('address')->label('مكان الإقامة الحالي')->required(),
            self::MARTIAL_STATUS => Select::make('marital_status')->label('الحالة الإجتماعية')
                ->required()
                ->searchable()->preload()
                ->options(MaritalStatus::getTranslatedEnum()),
            self::GENDER => Forms\Components\ToggleButtons::make('gender')->label('الجنس')
                ->default(Gender::MALE)
                ->required()->options(Gender::getTranslatedEnum())->inline(),
            self::WHATSAPP => PhoneInput::make('whatsapp')->label('رقم الواتساب')
                ->required()
                ->defaultCountry('SY')
                ->initialCountry('SY')
                ->inputNumberFormat(\Ysfkaya\FilamentPhoneInput\PhoneInputNumberType::INTERNATIONAL)
                ->displayNumberFormat(\Ysfkaya\FilamentPhoneInput\PhoneInputNumberType::INTERNATIONAL)
                ->dehydrateStateUsing(fn(string $state): string => str_replace(' ', '', trim($state))),
            self::EDUCATION_STATUS => Forms\Components\Group::make([ToggleButtons::make('education_status')
                ->inline()
                ->label('المستوى العلمي')
                ->options(EducationStatus::getTranslatedEnum())
                ->columnSpanFull()
                ->live()
                ->required(),
                TextInput::make('school')
                    ->visible(fn(Get $get) => in_array($get('education_status'), [EducationStatus::PRIMARY, EducationStatus::SECONDARY, EducationStatus::PREPARATORY]))
                    ->label('المدرسة')
                    ->required(),
                Select::make('class')
                    ->label('الصف')
                    ->options(config('helpers.classes'))
                    ->native(false)
                    ->required()
                    ->visible(fn(Forms\Get $get) => in_array($get('education_status'), [EducationStatus::PRIMARY, EducationStatus::SECONDARY, EducationStatus::PREPARATORY])),
                Select::make('university')
                    ->createOptionForm([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('الاسم')
                                ->unique('universities', 'name', ignoreRecord: true)
                        ])
                    ])->createOptionUsing(function (array $data) {
                        return University::create($data)->name;
                    })
                    ->options(University::pluck('name', 'name'))
                    ->searchable()
                    ->preload()
                    ->visible(fn(Forms\Get $get) => in_array($get('education_status'), [EducationStatus::UNIVERSITY, EducationStatus::POSTGRADUATE]))
                    ->label('الجامعة')
                    ->required(),
                Select::make('university_specialization')
                    ->visible(fn(Get $get) => in_array($get('education_status'), [EducationStatus::UNIVERSITY, EducationStatus::POSTGRADUATE]))
                    ->label('الاختصاص العلمي')
                    ->required()
                    ->options(UniversitySpecialization::pluck('name', 'name'))
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('التخصص')
                                ->unique('university_specializations', 'name', ignoreRecord: true)
                        ])
                    ])->createOptionUsing(function (array $data) {
                        return UniversitySpecialization::create($data)->name;
                    })
                    ->searchable(),
                ToggleButtons::make('graduated')->boolean()->label('متخرج ؟')->inline(true)->required()->visible(fn(Get $get)=>in_array($get('education_status'), [EducationStatus::UNIVERSITY, EducationStatus::POSTGRADUATE]))
            ])->columns(3)->columnSpanFull(),
            self::WORK => Select::make('work')
                ->label('العمل')
                ->required()
                ->createOptionForm([
                    Forms\Components\Grid::make()->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('العمل')
                            ->unique('works', 'name', ignoreRecord: true)
                    ])
                ])->createOptionUsing(function (array $data) {
                    return Work::create($data)->name;
                })
                ->options(Work::pluck('name', 'name'))
                ->preload()
                ->searchable(),
            self::OUTSIDE_COURSE => Forms\Components\TagsInput::make('outside_courses')
                ->label('كورسات خارجية')->columnSpan(2),
            self::NUMBER_FAMILY => TextInput::make('number_family')->numeric()->label('عدد افراد الأسرة'),
            self::EMAIL => TextInput::make('email')->email()->label('الإيميل'),
            self::FACEBOOK => TextInput::make('facebook')->url()->label('رابط صفحة الفيس بوك'),
            self::FATHER_PHONE => PhoneInput::make('father_phone')->initialCountry('SY')
                ->defaultCountry('SY')
                ->inputNumberFormat(\Ysfkaya\FilamentPhoneInput\PhoneInputNumberType::INTERNATIONAL)
                ->displayNumberFormat(\Ysfkaya\FilamentPhoneInput\PhoneInputNumberType::INTERNATIONAL)
                ->label('هاتف الأب'),
            self::MOTHER_PHONE => PhoneInput::make('mother_phone')->initialCountry('SY')
                ->defaultCountry('SY')
                ->inputNumberFormat(\Ysfkaya\FilamentPhoneInput\PhoneInputNumberType::INTERNATIONAL)
                ->displayNumberFormat(\Ysfkaya\FilamentPhoneInput\PhoneInputNumberType::INTERNATIONAL)
                ->label('هاتف الأم'),
            self::LANDLINE => TextInput::make('landline')->label('رقم الأرضي'),

            self::NATIONALITY => Select::make('nationality')->label('الجنسية')
                ->required()
                ->default('Syrian')
                ->options(config('helpers.nationalities'))
                ->searchable(),

            self::FATHER_WORK => TextInput::make('father_work')->label('عمل الاب'),
            self::COUNTRY => Select::make('country')
                ->label('البلد')
                ->required()
                ->default('SY')
                ->options(Helpers::prepareOptionsSpecificValue(config('helpers.countries'), 'name_ar', 'iso2'))
                ->searchable(),
        ];
    }

    public static function getFields(array $keys = [], $with_rules = true)
    {
        $service = new \App\Services\StudentService();
        $schools = self::key_value($service->getDistinctSchools());
        $university = self::key_value(config('helpers.syrian_university'));
        $university_specialization = self::key_value(config('helpers.specialization'));
        $works = self::key_value(config('helpers.work'));
        $countries = config('helpers.countries_by_key');
//        TODO Make Functional
        return collect([
            (new Field(__('dashboard.school'), self::SCHOOL, FieldType::LIST, true, $schools))->make(),
            (new Field(__('dashboard.class'), self::SCHOOL_CLASS, FieldType::LIST, false, self::key_value(config('helpers_student.classes'), true)))->make(),
            (new Field(__('dashboard.university'), self::UNIVERSITY, FieldType::LIST, true, $university))->make(),
            (new Field(__('dashboard.university_specialization'), self::UNIVERSITY_SPECIALIZATION, FieldType::LIST, true, $university_specialization))->make(),
            (new Field(__('dashboard.work'), self::WORK, FieldType::LIST, true, $works, rules: ['string']))->make(),
            (new Field(__('dashboard.outside_courses'), self::OUTSIDE_COURSE, FieldType::ARRAY, true, rules: ['array'], optional: true))->make(),
            (new Field(__('dashboard.number_family'), self::NUMBER_FAMILY, FieldType::NUMBER, true, rules: ['numeric']))->make(),
            (new Field(__('dashboard.email'), self::EMAIL, FieldType::EMAIL, true, rules: ['email']))->make(),
            (new Field(__('dashboard.father_work'), self::FATHER_WORK, FieldType::TEXT, false, rules: ['string']))->make(),
            (new Field(__('dashboard.facebook_account'), self::FACEBOOK, FieldType::URL, true, rules: ['url']))->make(),
            (new Field(__('dashboard.father_phone'), self::FATHER_PHONE, FieldType::PHONE, true, rules: ['phone:INTERNATIONAL']))->make(),
            (new Field(__('dashboard.mother_phone'), self::MOTHER_PHONE, FieldType::PHONE, true, rules: ['phone:INTERNATIONAL']))->make(),
            (new Field(__('dashboard.landline'), self::LANDLINE, FieldType::NUMBER, true))->make(),
            (new Field(__('dashboard.nationality'), self::NATIONALITY, FieldType::LIST, false, self::key_value($countries, true, 'name_ar'), rules: [new ValidCountry()]))->make(),
        ])->whereIn('name', $keys)->values()->toArray();
    }

    public static function getRules(array $fields): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $rules[$field['name']] = $field['rules'];
        }
        return $rules;
    }

}
