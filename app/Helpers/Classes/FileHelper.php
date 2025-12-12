<?php


namespace App\Helpers\Classes;


class FileHelper
{
    public static function getPublicPathFromUrl($url): string
    {
        return 'storage/'.\Str::after($url,'storage/');
    }
}
