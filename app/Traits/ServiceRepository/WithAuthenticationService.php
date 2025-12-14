<?php

namespace App\Traits\ServiceRepository;

use App\Enums\OtpType;
use App\Exceptions\RecordNotFound;
use App\Helpers\Classes\Interfaces\IOTP;
use App\Http\Resources\Api\V1\Customer\ProfileResource as CustomerProfileResource;
use App\Http\Resources\Api\V1\CarOwner\ProfileResource as CarOwnerProfileResource;
use App\Models\CarOwner;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\UnauthorizedException;

trait WithAuthenticationService
{
    public function register(array $data)
    {
        /** @var IOTP $model */
        $model = $this->mainRepository->create($data);
        $model->sendVerificationCode();
        return $this
            ->setMessage(__('تم تسجيل الحساب .. الرجاء تأكيد رقم الهاتف'))
            ->toJson();
    }

    public function login(string $phone, string $password, $fcm_token = null)
    {
        try {
            /** @var IOTP $model */
            $model = $this->mainRepository->get_by_phone($phone);
            if(!Hash::check($password,$model->password))
                return $this->setMessage(__('auth.failed'))
                    ->setCode(500)
                    ->toJson();
            if ($model instanceof Customer)
                $profileResourceClass = new CustomerProfileResource($model);
            else
                $profileResourceClass = new CarOwnerProfileResource($model);

            if ($fcm_token && $model->fcm_token !== $fcm_token)
                $model->update(['fcm_token' => $fcm_token]);

            $data = null;
            $message = __('mobile.Check You Phone To Verify Otp');
            $status_code = 200;
            if ($model->isVerified()) {
                $message = 'تم تسجيل الدخزل بنجاح';
                $data = [
                    'access_token' => $this->mainRepository->get_access_token($model),
                    'profile' => $profileResourceClass
                ];
            } else {
                $model->sendVerificationCode();
                $status_code =403;
            }

            return $this
                ->setMessage($message)
                ->setData($data)
                ->setCode($status_code)
                ->toJson();

        } catch (RecordNotFound $exception) {
            return $this->exceptionResponse($exception, 500)->toJson();
        } catch (\Exception $exception) {
            return $this->exceptionResponse($exception)->toJson();
        }
    }

    public function check_verify_otp(string $phone, $otp, $type = OtpType::AUTH, $fcmToken = null)
    {
        $verified = $this->mainRepository->check_verify_otp($phone, $otp, $type);
        $profileResourceClass = null;

        $data['verified'] = $verified;

        if ($verified) {
            $code = 200;
            $message = "Successfully Verify Otp";
            /** @var IOTP $model */
            $model = $this->mainRepository->get_by_phone($phone);
            $model->set_as_verified();
            $model=$model->refresh();
            if ($fcmToken && $model->fcm_token !== $fcmToken)
                $model->update(['fcm_token' => $fcmToken]);

            if ($model instanceof CarOwner)
                $profileResourceClass = new CarOwnerProfileResource($model);
            else if ($model instanceof Customer)
                $profileResourceClass = new CustomerProfileResource($model);

            if ($profileResourceClass) {
                $data = array_merge($data, [
                    'access_token' => $this->mainRepository->get_access_token($model),
                    'profile' => new $profileResourceClass($model)
                ]);
            }
        } else {
            $code = 500;
            $message = "Invalid OTP";
        }

        return $this
            ->setData($data)
            ->setCode($code)
            ->setMessage($message)
            ->toJson();
    }

    public function request_password_request(string $phone)
    {
        $model = $this->mainRepository->get_by_phone($phone);

        $model->sendVerificationCode(type: OtpType::PASSWORD_RESET);

        return $this
            ->setCode(200)
            ->setMessage('Successfully Re Send Otp')
            ->toJson();
    }

    public function reset_password(string $phone, string $otp, string $new_password)
    {
        if (!$this->mainRepository->check_verify_otp($phone, $otp, OtpType::PASSWORD_RESET))
            throw new \Exception(__('auth.failed'));

        $model = $this->mainRepository->get_by_phone($phone);

        $this->mainRepository->update($model->id, ['password' => $new_password]);

        $model->otp_password_reset()->delete();

        return $this
            ->setCode(200)
            ->setMessage('تم تغيير كلمة السر بنجاح')
            ->toJson();

    }

    public function resend_otp(string $phone, $type = OtpType::AUTH)
    {
        $model = $this->mainRepository->get_by_phone($phone);

        $model->sendVerificationCode(type:$type);

        return $this
            ->setCode(200)
            ->setMessage('تم إعادة ارسال رمز التفعيل بنجاح')
            ->toJson();
    }


}
