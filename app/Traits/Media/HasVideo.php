<?php

namespace App\Traits\Media;


use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasVideo
{
    use InteractsWithMedia;

    public function getCollection():string{
        return 'videos';
    }
    public function getVideos($collection=null): MediaCollection
    {
        return $this->getMedia($collection??$this->getCollection());
    }
    public function getFirstVideo($collection):string|null
    {
        return $this->getMedia($collection)->first()?->getFullUrl();
    }
    #[ArrayShape(['absolute_path' => "string", 'url' => "string", 'size' => "string", 'id' => "mixed", 'extension' => "string", 'date' => "mixed", 'name' => "mixed"])]
    private static function Resource(Media $item): array
    {
        return [
            'absolute_path'=>$item->getPath(),
            'url'=>$item->getFullUrl(),
            'size'=>$item->getHumanReadableSizeAttribute(),
            'id'=>$item->id,
            'extension'=>$item->getExtensionAttribute(),
            'date'=>$item->created_at->format('M d Y'),
            'name'=>$item->name
        ];
    }
    public function getVideosResource(): array
    {
        return $this->getVideos()->transform(fn(Media $media) => self::Resource($media))->toArray();
    }
    public function saveVideo($Video,$collection='Videos'): Media
    {
         return $this
            ->addMedia($Video)
            ->toMediaCollection($collection);
    }
    public function clearVideo($collection){
        $this->getFirstMedia($collection)?$this->getMedia($collection)->each->delete():null;
        $this->clearMediaCollection($collection);
    }


//    public function

}
