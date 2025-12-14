<?php


namespace App\Traits;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

trait HasUuid
{
    public static $fakeUuid = null;

    public static function bootHasUuid(): void
    {
        static::creating(function (Model $model): void {
            /** @phpstan-ignore-next-line */
            $model->uuid = static::$fakeUuid ?? $model->uuid ?? (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public static function findByUuid($uuid): ?static
    {
        // PostgreSQL fails the query when an uuid column is compared to a non-uuid value
        if (! is_string($uuid) || ! Uuid::isValid($uuid)) {
            return null;
        }

        return static::where('uuid', $uuid)->first();
    }

    public static function firstOrFailByUuid($uuid): self
    {
        return static::where('uuid', $uuid)->firstOrFail();
    }
}
