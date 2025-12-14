<?php

namespace App\Traits\ServiceRepository;


trait WithFavoriteService
{
    function get_favorites($favoriter,$favoriteItemClass,$callbackResource,$with=[])
    {
        $data=$favoriter->getFavoriteItems($favoriteItemClass)->with($with)->get();
        return $this
            ->setCode(200)
            ->setData($callbackResource($data))
            ->setMessage('Successfully Get Favorites')
            ->toJson();
    }

    function toggle_favorite($favoriter,$favoriteable,bool $status)
    {
        if($status && !$favoriter->hasFavorited($favoriteable))
            $favoriter->favorite($favoriteable);
        else if(!$status && $favoriter->hasFavorited($favoriteable))
            $favoriter->unfavorite($favoriteable);

        return $this
            ->setCode(200)
            ->setData(null)
            ->setMessage('تم تغيير حالة المفضلة بنجاح')
            ->toJson();
    }
}
