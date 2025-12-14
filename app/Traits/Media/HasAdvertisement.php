<?php

namespace App\Traits\Media;


use App\Models\Advertisement;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasAdvertisement
{
    public function advertisements(){
        return $this->morphMany(Advertisement::class,'relatable');
    }

}
