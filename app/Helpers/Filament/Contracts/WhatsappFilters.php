<?php


namespace App\Helpers\Filament\Contracts;


use App\Helpers\Helpers;
use App\Models\Student;
use Illuminate\Support\Str;
use Modules\University\App\Facades\StudentService;
use Modules\Whatsapp\App\Enums\WhatsappMessageStatus;
use Modules\Whatsapp\App\Models\WhatsappTemplate;

abstract class WhatsappFilters
{
    public abstract function filter_components(): array;

    public abstract function variables(): array;

    public abstract function preparePayload(WhatsappTemplate $whatsappTemplate);

    public function attachStudentsToTemplate($students, WhatsappTemplate $whatsappTemplate, string $content)
    {
        foreach ($students as $student) {
            $student_information = [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_whatsapp' => $student->whatsapp,
                'student_username' => $student->username,
                'content' => '',
                'send' => false
            ];
            if (Str::contains($content, '{student_weekly_program}'))
                $student_group_information['student_weekly_program'] = StudentService::get_weekly_program($student);
            $whatsappTemplate->receivers()->syncWithPivotValues([$student->id], ['status' => WhatsappMessageStatus::PENDING, 'content' => Helpers::replaceVariables($content, $student_information)], false);
        }
    }
}
