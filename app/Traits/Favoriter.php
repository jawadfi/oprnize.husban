<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait Favoriter
{
 use \Overtrue\LaravelFavorite\Traits\Favoriter;
    public function favorite(Model $object): void
    {
        if (! $this->hasFavorited($object)) {
            $favorite = app(config('favorite.favorite_model'));
            $favorite->{config('favorite.user_foreign_key')} = $this->getKey();
            $favorite->user_type = $this::class;
            $object->favorites()->save($favorite);
        }
    }
    public function unfavorite(Model $object): void
    {
        $relation = $object->favorites()
            ->where('favoriteable_id', $object->getKey())
            ->where('favoriteable_type', $object->getMorphClass())
            ->where('user_type', $this::class)
            ->where(config('favorite.user_foreign_key'), $this->getKey())
            ->first();

        if ($relation) {
            $relation->delete();
        }
    }

    public function hasFavorited(Model $object): bool
    {
        return ($this->relationLoaded('favorites') ? $this->favorites : $this->favorites())
                ->where('favoriteable_id', $object->getKey())
                ->where('user_type', $this::class)
                ->where('favoriteable_type', $object->getMorphClass())
                ->count() > 0;
    }

    public function favorites(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this
            ->hasMany(config('favorite.favorite_model'), config('favorite.user_foreign_key'), $this->getKeyName())
            ->where('user_type', $this::class);
    }
    public function getFavoriteItems(string $model)
    {
        $relationships = $this::class === User::class ? 'favoriters' : 'favoriters_stores';
        return app($model)->whereHas(
            $relationships,
            function ($q) {
                return $q
                    ->where(config('favorite.user_foreign_key'), $this->getKey());
            }
        );
    }
}
