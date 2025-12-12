<?php


namespace App\Helpers;


use App\Core\Whatsapp\Service\ChatService;
use App\Enums\WeekDays;
use App\Models\Course;
use App\Models\Project;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Intervention\Image\Facades\Image;
use Modules\Whatsapp\App\Enums\MessageType;
use Modules\Whatsapp\App\Models\WhatsappSession;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use function PHPUnit\Framework\matches;

class Helpers
{
    public static function generateArrayForSelectStage($stages): array
    {
        $arrayStages = [];
        for ($i = 1; $i <= $stages; $i++) {
            $arrayStages[$i] = __("numbers.masculine." . $i);
        }
        return $arrayStages;
    }
    /**
     * Normalize a Syrian phone number to +963... format.
     *
     * Examples:
     *  - "0933518062"     -> "+963933518062"
     *  - "00963933800127" -> "+963933800127"
     *  - "+963982262779"  -> "+963982262779"
     *
     * @param string $inputPhone Raw phone string
     * @return string|null Normalized phone or null if invalid/unhandled
     */
    public static function normalizeSyrianPhone(string $inputPhone): ?string
    {
        // Trim and remove common separators (spaces, dashes, parentheses)
        $s = trim($inputPhone);
        $s = str_replace([' ', '-', '(', ')', '.', "\t", "\n", "\r"], '', $s);

        if ($s === '') {
            return null;
        }

        // If already in E.164 +963 form, just return
        if (strpos($s, '+963') === 0) {
            // Optional: ensure the rest are digits
            if (preg_match('/^\+963\d+$/', $s)) {
                return $s;
            }
            return null; // malformed +963...
        }

        // If starts with "00" (international prefix), remove the leading "00"
        if (strpos($s, '00') === 0) {
            $s = substr($s, 2);
            // If after removing 00 we have '963...' ensure + is added
            if (strpos($s, '963') === 0) {
                if (preg_match('/^963\d+$/', $s)) {
                    return '+' . $s;
                }
                return null;
            }
            // If not country code 963, we won't handle — return null or you could add support
            return null;
        }

        // If starts with single '0' local national format: 0XXXXXXXXX (remove leading 0, prepend +963)
        if (strpos($s, '0') === 0) {
            $rest = substr($s, 1);
            // Expect the rest to be digits
            if (preg_match('/^\d+$/', $rest)) {
                // If the input already included the local trunk + country incorrectly (e.g. 0963...), you may want to handle that.
                // For typical mobile 09xxxxxxxx -> convert to +963 + rest without the initial 0
                return '+963' . $rest;
            }
            return null;
        }

        // If it already starts with 963 (no plus), add plus
        if (strpos($s, '963') === 0) {
            if (preg_match('/^963\d+$/', $s)) {
                return '+' . $s;
            }
            return null;
        }

        // Otherwise, not recognized/invalid for Syrian normalization
        return null;
    }
    public static function getFullNumberFixed($phone, $code = "+963")
    {
        // Remove any spaces, dashes, or special characters from the phone number
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if(Str::substr($phone,0,2)=='00'){

        }
        // Check if the phone number has exactly 9 digits
        if (strlen($phone) !== 9) {
            return null;
        }

        // Return the code concatenated with the phone number
        return $code . $phone;
    }

    public static function splitFullNameIntoFirstMiddleLast($name)
    {
        // Trim and clean the name
        $name = trim($name);

        // Split the name by spaces
        $parts = preg_split('/\s+/', $name);

        $first_name = '';
        $middle_name = '';
        $last_name = '';

        // Handle empty or single name
        if (count($parts) === 0) {
            return [
                'first_name' => '',
                'middle_name' => '',
                'last_name' => '',
            ];
        }

        if (count($parts) === 1) {
            return [
                'first_name' => $parts[0],
                'middle_name' => '',
                'last_name' => '',
            ];
        }

        // Check if the first part is "عبد" - it should be combined with the next part
        if ($parts[0] === 'عبد' && count($parts) > 1) {
            $first_name = $parts[0] . ' ' . $parts[1];
            array_shift($parts); // Remove first element
            array_shift($parts); // Remove second element
        } else {
            $first_name = array_shift($parts); // Take the first part
        }

        // Two parts remaining (middle and last)
        if (count($parts) === 2) {
            $middle_name = $parts[0];
            $last_name = $parts[1];
        } // One part remaining (last name only)
        elseif (count($parts) === 1) {
            $last_name = $parts[0];
        } // More than two parts remaining (combine all middle parts, last is the final part)
        elseif (count($parts) > 2) {
            $last_name = array_pop($parts); // Take the last part as last name
            $middle_name = implode(' ', $parts); // Everything else is middle name
        }

        return [
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
        ];
    }

    public static function getNumberStageByString($stage)
    {
        // Trim and normalize the stage string
        $stage = trim($stage);

        return match ($stage) {
            'الأول' => 1,
            'الثاني' => 2,
            'الثالث' => 3,
            'الرابع' => 4,
            'الخامس' => 5,
            'السادس' => 6,
            default => null,
        };
    }

    public static function getStageStringByNumber($stage)
    {
        return match ($stage) {
            1 => 'الأول',
            2 => 'الثاني',
            3 => 'الثالث',
            4 => 'الرابع',
            5 => 'الخامس',
            6 => 'السادس',
            default => null,
        };
    }

    public static function getResultText($template, $variables): array|string
    {
        // استخدام str_replace لاستبدال المتغيرات بالقيم
        return str_replace(
            array_map(function ($key) {
                return '{' . $key . '}';
            }, array_keys($variables)), // إنشاء مصفوفة من المفاتيح بصيغة {key}
            array_values($variables), // إنشاء مصفوفة من القيم
            $template // النص الرئيسي
        );
    }

    public static function append_to_array_file($file, $value)
    {
        $array = json_decode(file_get_contents($file), true);
        $array[] = $value;
        file_put_contents($file, json_encode($array));
    }

    public static function get_file_json_content($file)
    {
        return json_decode(file_get_contents($file), true);
    }

    public static function calculateNearestDate($day): string
    {
        // Get today’s date
        $today = Carbon::now();

        // Map the names of the days to their respective numerical representation
        $days = [
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
            'sunday' => Carbon::SUNDAY,
        ];

        // Get the target day as a number from the group model
        $targetDay = strtolower($day);

        if (!array_key_exists($targetDay, $days)) {
            throw new \InvalidArgumentException("Invalid day of the week: {$targetDay}");
        }

        if ($today->dayOfWeek === $days[$targetDay])
            return $today->toDateString();
        // Get the next occurrence of the target day
        $nearestDate = $today->next($days[$targetDay]);

        return $nearestDate->toDateString(); // Return the date as a string (YYYY-MM-DD)
    }

    public static function get_same_key_value($array): array
    {
        return collect($array)->mapWithKeys(fn($value, $key) => [$value => $value])->toArray();
    }

    public static function get_project_by_panel($panel): Project|Builder|null
    {
        return Project::ofPanel($panel)->first();
    }

    public static function get_courses_current_project($panel = null)
    {
        return self::get_current_project($panel)->courses;
    }

    public static function get_current_project($panel = null): ?Project
    {
        if (!$panel)
            $panel = Filament::getCurrentPanel()->getId();
        return Project::ofPanel($panel)->first();
    }
    public static function appendValue($value, $file): bool
    {
        // If file doesn't exist, start with empty array
        if (!file_exists($file)) {
            $data = [];
        } else {
            $contents = @file_get_contents($file);
            if ($contents === false || $contents === '') {
                $data = [];
            } else {
                // Decode existing JSON
                $data = json_decode($contents, true);
                if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                    // Invalid JSON -- you can choose to fail or back up the bad file.
                    return false;
                }
                // Ensure it's an array (JSON root must be array for this method)
                if (!is_array($data)) {
                    return false;
                }
            }
        }

        // Append the new value
        $data[] = $value;

        // Encode to JSON with pretty print (optional). Use JSON_UNESCAPED_UNICODE to keep unicode readable.
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        // Write atomically: write to temp file then rename
        $tmpFile = $file . '.tmp';
        if (@file_put_contents($tmpFile, $json, LOCK_EX) === false) {
            return false;
        }
        // On successful write, rename (atomic on most systems)
        if (!@rename($tmpFile, $file)) {
            // cleanup temp file if rename failed
            @unlink($tmpFile);
            return false;
        }

        return true;
    }
    public static function replaceVariables(string $content, array $data): array|string|null
    {
        return preg_replace_callback('/{(\w+)}/', function ($matches) use ($data) {
            // Return the corresponding value or the original placeholder if not found
            return isset($data[$matches[1]]) ? $data[$matches[1]] : $matches[0];
        }, $content);
    }

    public static function get_current_course($panel = null, $course_id = null): ?Course
    {
        if (!$panel)
            $panel = Filament::getCurrentPanel()->getId();

        if ($course_id)
            return Project::ofPanel($panel)->first()->courses()->find($course_id);

        return Project::ofPanel($panel)->first()?->active_course;
    }

    public static function get_course_from_url(): string|null
    {
        $course_id = Str::before(Str::afterLast(URL::current(), 'courses/'), '/');
        if (is_numeric($course_id))
            return $course_id;
        return null;
    }

    public static function prepareOptionsSpecificValue($array, $label, $val): array
    {
        return collect($array)->mapWithKeys(fn($value, $key) => [$value[$val] => $value[$label]])->toArray();
    }

    static function get_students_fields(): \Illuminate\Support\Collection
    {
        $model = (new Student());
        return collect($model->getFillable())
            ->filter(fn($field) => !in_array($field, $model::EXCEPT_FILLABLE));
    }

    static function prepare_students_fields_for_options(): array
    {
        return self::get_students_fields()->mapWithKeys(fn($field) => [
            $field => __("dashboard.$field")
        ])->toArray();
    }

    public static function same_key_value($array, $set_key = true): array
    {
        return collect($array)->mapWithKeys(fn($value, $key) => $set_key ? [$key => $key] : [$value => $value])->toArray();
    }

    public static function key_with_exists_value($array_keys, $array_values, $set_key = true): array
    {
        $keys = self::same_key_value($array_keys, $set_key);
        return collect($keys)->mapWithKeys(fn($key) => [$key => $array_values[$key]])->toArray();
    }

    public static function getStudentByUsername(string $username)
    {
        return Student::whereHas('usernames', function (Builder $query) use ($username) {
            $query->where('student_usernames.username', $username);
        })->orWhere('students.username',$username)->first();
    }

    public function get_translate_day($day): string
    {
        return [
            WeekDays::SATURDAY => "السبت",
            WeekDays::SUNDAY => "الأحد",
            WeekDays::MONDAY => "الإثنين",
            WeekDays::TUESDAY => "الثلاثاء",
            WeekDays::WEDNESDAY => "الأربعاء",
            WeekDays::THURSDAY => "الخميس",
            WeekDays::FRIDAY => "الجمعة",
        ][$day];
    }

    public static function generate_qr($content): string
    {
        return base64_encode(QrCode::format('png')->size(400)->generate($content));
    }

    public static function fix_base_64_ext($media)
    {
        if (Str::contains($media->mime_type, 'image/'))
            $correct_ext = Str::afterLast($media->mime_type, 'image/');
        else
            $correct_ext = Str::afterLast($media->mime_type, 'application/');
        $path_image = Str::afterLast($media->getFullUrl(), 'storage/');
        $file = Str::afterLast($path_image, '/');
        $file_name = Str::before($file, '.');
        $replaced_path_image = Str::replace($file, "$file_name.$correct_ext", $path_image);
        $rename_file = rename(public_path("storage/$path_image"), public_path("storage/$replaced_path_image"));
        $media->update(['file_name' => "$file_name.$correct_ext"]);
    }

    public static function getFileNameMediaFromUrl($url): ?string
    {
        $media_file = Str::afterLast($url, 'storage/');
        $split_id_and_file = explode('/', $media_file);
        $media_id = $split_id_and_file[0];
        $file_name = $split_id_and_file[1];
        $extension = explode('.', $file_name)[1];
        $media = Media::query()->where('id', $media_id)->firstWhere('file_name', $file_name);
        if ($media)
            return $media?->name . ".$extension";
        return null;
    }

    public static function get_type_message_by_enum($enum): string
    {
        if ($enum === MessageType::TEXT)
            return 'text';
        else if ($enum === MessageType::IMAGE)
            return 'image';
        else if ($enum === MessageType::VIDEO)
            return 'video';
        else if ($enum === MessageType::FILE)
            return 'file';
        else
            return 'text';
    }

    public static function convert_to_arabic_number($string): array|string
    {
        $western_arabic = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $eastern_arabic = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');

        return str_replace($western_arabic, $eastern_arabic, $string);
    }

    public static function prepare_vcard_to_import($data, WhatsappSession $session)
    {
        foreach ($data as $record) {
            try {
                $phone = $record->phone['CELL'][0];

                $session->contacts()->create([
                    'name' => $record->fullname,
                    'mobile' => Str::replace(' ', "", $phone)
                ]);

            } catch (\Exception $exception) {
                continue;
            }
        }
    }

    public static function countries_by_key_value(): array
    {
        return collect(config('helpers.countries_by_key'))->mapWithKeys(function ($value, $key) {
            return [$value['iso2'] => $value['name']];
        })->toArray();
    }

    public static function extract_all_curly_braces($string): array
    {
        $matches = [];
        preg_match_all('/{(.*?)}/', $string, $matches);
        return isset($matches[1]) ? $matches[1] : [];
    }

    public static function check_curly_braces($string): bool
    {
        return count(self::extract_all_curly_braces($string)) > 0;
    }

    public static function generateQrWithLogoFriday($username): string
    {
        $qr_base_64 = base64_encode(QrCode::format('png')->size(600)
            ->generate($username));
        $original_image = \Spatie\Image\Image::load(public_path('assets/projects/friday/qr-code.jpg'));

        $watermark_qr = Image::make($qr_base_64)->save(public_path('assets/projects/friday/qr-generated/qr.png'));

        $original_image = $original_image->watermark(public_path('assets/projects/friday/qr-generated/qr.png'))
            ->watermarkPosition(Manipulations::POSITION_TOP_LEFT)
            ->watermarkPadding(300, 750, Manipulations::UNIT_PIXELS);

        $original_image->save(public_path('assets/projects/friday/qr-generated/result.jpg'));

        return config('app.url') . '/assets/projects/friday/qr-generated/result.jpg';
    }

    public static function generateQrWithLogo($username, Project $project): string
    {
        $qr_base_64 = base64_encode(QrCode::format('png')->size(600)
            ->generate($username));
        $panel = $project->panel;
        $original_image = \Spatie\Image\Image::load(public_path("assets/projects/$panel/qr-code.jpg"));

        $watermark_qr = Image::make($qr_base_64)->save(public_path("assets/projects/$panel/qr-generated/qr.png"));

        $original_image = $original_image->watermark(public_path("assets/projects/$panel/qr-generated/qr.png"))
            ->watermarkPosition(Manipulations::POSITION_TOP_LEFT)
            ->watermarkPadding(300, 750, Manipulations::UNIT_PIXELS);

        $original_image->save(public_path("assets/projects/$panel/qr-generated/result.jpg"));

        return config('app.url') . "/assets/projects/$panel/qr-generated/result.jpg";
    }

    public static function getWhatsappSessionProject($project = null)
    {
        if (!$project)
            $project = Helpers::get_current_project();
        return $project?->whatsapp_sessions()?->first();
    }

    public static function sendQrCodeForStudentByProject(Project $project, Student $student)
    {
        try {
            $panel = $project?->panel;

            $registration_message = setting($panel . '.notifications.register_student', '');

            $message = Helpers::getResultText($registration_message, [
                'name' => $student->name
            ]);

            $whatsappSession = self::getWhatsappSessionProject($project);

            if (!$whatsappSession)
                return;

            $qr = Helpers::generateQrWithLogo($student->username, $project);

            (new ChatService($whatsappSession))->sendImageMessage($student->whatsapp, $qr, $message);

            $student->update(['qr_send' => true]);
        } catch (\Exception $exception) {
            $student->update(['qr_send' => false]);
        }
    }
}
