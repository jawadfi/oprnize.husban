<?php


namespace App\Helpers\Classes\Interfaces;


interface IOTP
{
    public function sendOTP($receiver):int;
}
