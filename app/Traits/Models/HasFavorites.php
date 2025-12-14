<?php


namespace App\Traits\Models;


use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasFavorites
{
    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'user');
    }

    public function favorite_stores()
    {
        return $this->morphToMany(Store::class, 'user', 'favorites', relatedPivotKey: 'favorite_id')->where('favorite_type',Store::class);
    }

    public function favorite_categories()
    {
        return $this->morphToMany(Category::class, 'user', 'favorites', relatedPivotKey: 'favorite_id')->where('favorite_type',Category::class);
    }

    public function favorite_products()
    {
        return $this->morphToMany(Product::class, 'user', 'favorites', relatedPivotKey: 'favorite_id')->where('favorite_type',Product::class);
    }

    public function addFavorite($model)
    {
        if (!$this->checkFavorite($model))
            return $this->favorites()->create(['favorite_id' => $model->id, 'favorite_type' => $model::class]);
    }

    public function removeFavorite($model)
    {
        if ($this->checkFavorite($model))
            return $this->favorites()->whereMorphedTo('favorite',$model)->delete();
    }

    public function checkFavorite($model): bool
    {
        return $this->favorites()->whereMorphedTo('favorite', $model)->exists();
    }
}
