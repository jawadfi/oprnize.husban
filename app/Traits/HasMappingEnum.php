<?php


namespace App\Traits;

use JetBrains\PhpStorm\Pure;

trait HasMappingEnum
{
    public static function getTranslatedEnum(): array
    {
        return [];
    }
    #[Pure]
    public static function getTranslatedKey($key)
    {
        return self::getTranslatedEnum()[$key]??$key;
    }
    public static function getColors():array{
        return [];
    }
    public static function getIcons():array{
        return [];
    }
    #[Pure]
    public static function getColor($key)
    {
        return self::getColors()[$key]??'primary';
    }
    #[Pure]
    public static function getIcon($key)
    {
        return self::getIcons()[$key]??'';
    }
}
