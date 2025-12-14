<?php


namespace App\Traits\ServiceRepository;

use App\Enums\OtpType;
use App\Exceptions\RecordNotFound;
use App\Helpers\Classes\Interfaces\IOTP;
use Illuminate\Support\Facades\Hash;

trait WithAuthEloquent
{
    // Write something awesome :)
    function get_by_phone_password(string $phone, $password): IOTP
    {
        /** @var IOTP $model */
        $model = $this->model->firstWhere('phone', $phone);


        if (!$model || !Hash::check($password, $model->password))
            throw new RecordNotFound(__('auth.failed'));
        return $model;

    }

    #[Pure]
    function check_verified(IOTP $model): bool
    {
        return $model->verified_at !== null;
    }

    function check_verify_otp(string $phone, $otp, $type = OtpType::AUTH, $set_verified = false): bool
    {
        /** @var IOTP $model */
        $model = $this->model->firstWhere('phone', $phone);

        if (!$model)
            throw new RecordNotFound(__('mobile.Phone Number Not Registered !'));

        return $model->checkOTP($otp, $type);
    }

    function get_by_phone(string $phone): IOTP
    {
        /** @var IOTP $model */
        $model = $this->model->firstWhere('phone',$phone);

        if(!$model)
            throw new RecordNotFound(__('mobile.Phone Number Not Registered !'));

        return $model;
    }

    function get_access_token($model): string
    {
        return $model->createToken('Mobile Application')->plainTextToken;
    }

}
