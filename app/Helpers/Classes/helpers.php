<?php
//
use App\Helpers\Classes\Enum\ActivityType;
use App\Helpers\Classes\Settings\WhatsappSetting;
use App\Models\Student;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\Pure;
use Kutia\Larafirebase\Facades\Larafirebase;
use libphonenumber\NumberParseException;
use Spatie\Activitylog\Models\Activity;

if(!function_exists('whatsapp_setting')) {
    function whatsapp_setting(): WhatsappSetting
    {
        return new WhatsappSetting();
    }
}
if(!function_exists('get_status_question_correct')) {
    function get_status_question_correct($value,$is_radio=true){
        if($value && !$is_radio)
            return __('student.done_answer_correcting');

        if($value === null)
            return __('student.answer_correcting');
        else if($value == true)
            return __('student.answer_corrected');
        else
            return __('student.answer_wrong');
    }
}
if(!function_exists('get_whatsapp_template_by_identifier')) {
    function get_whatsapp_template_by_identifier(string $session): WhatsappSetting
    {
        return WhatsappTemplate::all()
            ->filter(fn(WhatsappTemplate $template)=>$template->identifier===$session)
            ->first();
    }
}
if(!function_exists('getTranslatedStage')) {
    function getTranslatedStage($stage){
        if(is_numeric($stage))
            return __('numbers.masculine.'.$stage);
        else
            return __('dashboard.'.$stage);
    }
}
if(!function_exists('getResourceStage')) {
    function getResourceStage($stage){
        if(!$stage)
            return null;
        return [
            'value'=>$stage,
            'label'=>getTranslatedStage($stage)
        ];
    }
}

if(!function_exists('normalize_name')) {
    function normalize_name($name) {
        $patterns     = array( "/إ|أ|آ/" , "/َ|ً|ُ|ِ|ٍ|ٌ|ّ/",'/ْ/' );
        $replacements = array( "ا" , "",""         );
        return preg_replace($patterns, $replacements, $name);
    }
}
if(!function_exists('get_format_mobile_number')) {
    function get_format_mobile_number($number): array|string|null
    {
        if(!$number)
            return null;
        $phoneNumber =  new \Propaganistas\LaravelPhone\PhoneNumber($number);
        try {
            $phone_obj = $phoneNumber->toLibPhoneObject();
        } catch (NumberParseException $e) {
            return $number;
        }
        $country_code='+'.$phone_obj->getCountryCode();
        return [
            'code'=>$country_code,
            'phone'=>$phone_obj->getNationalNumber(),
            'country'=>get_country_by_country_code($country_code),
        ];
    }
}
// Ex +963 ----> SY
if(!function_exists('get_country_by_country_code')) {
    function get_country_by_country_code($code) {
        return collect(config('helpers.countries'))?->firstWhere('dial_code',$code)['iso2']??null;
    }
}

if(!function_exists('student_by_username')) {
    function student_by_username($username):Student|null
    {
        return Student::firstWhere('username');
    }
}
if(!function_exists('get_text')) {
    function get_text($html):string
    {
        $html = new \Html2Text\Html2Text($html);
        return $html->getText();
    }
}
if(!function_exists('me')) {
    function me($guard=''): \App\Models\User|\Illuminate\Contracts\Auth\Authenticatable
    {
        return auth($guard)->user();
    }
}
if(!function_exists('getCompanyId')) {
    function getCompanyId($guard = 'company'): ?int
    {
        $user = auth($guard)->user();
        
        if ($user instanceof \App\Models\Company) {
            return $user->id;
        }
        
        if ($user instanceof \App\Models\User) {
            return $user->company_id;
        }
        
        return null;
    }
}
if(!function_exists('getCompany')) {
    function getCompany($guard = 'company'): ?\App\Models\Company
    {
        $user = auth($guard)->user();
        
        if ($user instanceof \App\Models\Company) {
            return $user;
        }
        
        if ($user instanceof \App\Models\User) {
            $user->load('company');
            return $user->company;
        }
        
        return null;
    }
}
if(!function_exists('getUserIdByScantumToken')) {
    function getUserIdByScantumToken($token):int|null
    {
        [$id, $user_token] = explode('|', $token, 2);
        $token_data = DB::table('personal_access_tokens')->where('token', hash('sha256', $user_token))->first();
        if($token_data){
        $user_id = $token_data->tokenable_id;
        return $user_id;
        }
        return null;
    }
}
if(!function_exists('getStudentByRequestKey')) {
    function getStudentByRequestKey($key='token'):\App\Models\Student|null
    {
       return \App\Models\Student::find(getUserIdByScantumToken(request($key)));
    }
}
if(!function_exists('getFlutterUrl')) {
    function getFlutterUrl():string
    {
      return 'toFlutter';
    }
}
if(!function_exists('getAuthorization')) {
    function getAuthorization()
    {
        return explode(' ',request()->header('Authorization'))[1];
    }
}

if(!function_exists('deleteValueFromArray')) {
    function deleteValueFromArray($del_val,array $data): array
    {
        if (($key = array_search($del_val, $data)) !== false) {
            unset($data[$key]);
        }
        return array_keys($data);
    }
}
if(!function_exists('confirm_message')) {
    function confirm_message(): string
    {
        return __('dashboard.are_you_sure');
    }
}
if(!function_exists('lpad')) {
    function lpad($value,$length=5,$symbol='0'): string
    {
        return Str::padLeft($value,$length,$symbol);
    }
}
if(!function_exists('prepareErrorText')) {
    function prepareErrorText(array $errors): string
    {
        $text="\n";
        foreach ($errors as $attribute=>$error){
            foreach ($error as $message){
              $text.="$attribute:$message\n";
            }
        }
        return $text;
    }
}

if(!function_exists('get_storage_path')) {
    function get_storage_path($file): string
    {
        return config('app.url')."/storage/".$file;
    }
}

if(!function_exists('getIconActivity')) {
    function getIconActivity(Activity $activity):string
    {
        $type=$activity->getExtraProperty('type');
        return [
            ActivityType::SELF_REGISTER=>'la-user-check',
            ActivityType::START_EXAM=>'la-book-reader',
            ActivityType::END_EXAM=>'la-check-circle',
            ActivityType::UPLOAD_LECTURE=>'la-file-upload',
        ][$type]??'la-check-circle';
    }
}
if(!function_exists('getColorActivity')) {
    function getColorActivity(Activity $activity): string
    {
        $type=$activity->getExtraProperty('type');
        return [
            ActivityType::SELF_REGISTER=>'bg-soft-info',
            ActivityType::START_EXAM=>'bg-soft-primary',
            ActivityType::END_EXAM=>'bg-soft-success',
            ActivityType::UPLOAD_LECTURE=>'bg-soft-primary',
        ][$type]??'bg-soft-primary';
    }
}

if(!function_exists('getFileSize')) {
    function getFileSize(string $path): string
    {
        $absolute_path=getAbsolutePath($path);
        return humanFileSize(filesize($absolute_path),"MB");
    }
}
if(!function_exists('getAbsolutePath')) {
    function getAbsolutePath(string $path): string
    {
        return public_path('storage'.Str::after($path,'/storage'));
    }
}
if(!function_exists('getCountryByKey')) {
    function getCountryByKey(string $key)
    {
        return collect(config('helpers.countries'))->where('iso2',strtoupper($key))->first()??['name_ar'=>'not'];
    }
}
if(!function_exists('getDateFileCreated')) {
    #[Pure] function getDateFileCreated(string $file): string
    {
        $absolute_path=getAbsolutePath($file);
        return date("d F Y", filemtime($absolute_path));
    }
}

if(!function_exists('getFileName')) {
    function getFileName(string $file): string
    {
        return Str::before(Str::afterLast($file,'/'),'.');
    }
}

if(!function_exists('getExtensionFile')) {
    function getExtensionFile(string $path): string
    {
        return Str::afterLast($path,'.');
    }
}

if(!function_exists('send_fcm_notification')) {
    function send_fcm_notification(string $fcm_token,string $title,string $body,array $data=[]): string
    {
        return Larafirebase::withTitle($title)->withBody($body)->sendNotification($fcm_token);
    }
}
if(!function_exists('humanFileSize')) {
    #[Pure] function humanFileSize($size, $unit = ""): string
    {
        if ((!$unit && $size >= 1 << 30) || $unit == "GB")
            return number_format($size / (1 << 30), 2) . " GB";
        if ((!$unit && $size >= 1 << 20) || $unit == "MB")
            return number_format($size / (1 << 20), 2) . " MB";
        if ((!$unit && $size >= 1 << 10) || $unit == "KB")
            return number_format($size / (1 << 10), 2) . " KB";
        return number_format($size) . " bytes";
    }
}
