<?php


namespace App\Traits;


use App\Enums\OtpType;
use App\Helpers\Helpers;
use App\Helpers\Whatsapp;
use App\Models\OtpVerification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasOTPVerification
{
    public function otp_auth(): MorphOne
    {
        return $this->morphOne(OtpVerification::class, 'related')
            ->where('type', OtpType::AUTH);
    }

    public function otp_password_reset(): MorphOne
    {
        return $this->morphOne(OtpVerification::class, 'related')
            ->orderBy('created_at')
            ->where('type', OtpType::PASSWORD_RESET);
    }

    public function checkOTP($code, $type = OtpType::AUTH): bool
    {
        $otp = null;
        if ($type === OtpType::AUTH)
            $otp = $this->otp_auth;
        else
            $otp = $this->otp_password_reset;
        if (!$otp)
            return false;
        if ($otp->expire_at && $otp->expire_at->isPast())
            return false;

        return $otp->code === $code;
    }

    public function sendVerificationCode($expire_at = null, $type = OtpType::AUTH): Model
    {
        $code = rand(1111, 9999);

        $this->send_otp($code);

        if ($type === OtpType::AUTH)
            return $this->otp_auth()->updateOrCreate(['type' => $type], ['code' => $code, 'expire_at' => $expire_at, 'type' => $type]);
        else
            return $this->otp_password_reset()->updateOrCreate(['type' => $type], ['code' => $code, 'expire_at' => $expire_at, 'type' => $type]);
    }

    public function set_as_verified()
    {
        if ($this->verified_at === null) {
            $this->update(['verified_at' => now()]);
        }
        $this->otp_auth()->delete();
    }

    public function clear_verified($type = OtpType::AUTH)
    {
        if ($type == OtpType::AUTH) {
            $this->update([$this->verified_at => null]);
            $this->otp_auth()->delete();
        } else {
            $this->otp_password_reset()->delete();
        }
    }

    public function send_otp($code, $message = null)
    {
        $message = $message ?? "Your Verification Code Is : *$code*";
        (new Whatsapp())->sendTextMessage(
            $this->{$this->mobile_key()},
            $message
        );
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    function mobile_key(): string
    {
        return 'phone';
    }

    function verified_column(): string
    {
        return 'verified_at';
    }
}
