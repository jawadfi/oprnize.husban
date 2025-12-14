<?php

namespace App\Traits\Media;


use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasFile
{
    public function getFile($collection=null)
    {
        if(!$collection)
            $collection=$this->getFileCollection();
        return $this->getMedia($collection)->first();
    }
    private static function ResourceFile($item): array
    {
        return [
            'absolute_path'=>$item->getPath(),
            'url'=>$item->getFullUrl(),
            'size'=>$item->human_readable_size,
            'id'=>$item->id,
            'extension'=>$item->extension,
            'date'=>$item->created_at->format('M d Y'),
            'name'=>$item->name
        ];
    }
    public function getFileResource($collection=null)
    {
        if(!$collection)
            $collection=$this->getFileCollection();
        $media = $this->getFirstMedia($collection);
        if(!$media)
            return null;
        return self::ResourceFile($this->getFirstMedia($collection));
    }
    public function saveFile($file,$collection=null): Media
    {
        if(!$collection)
            $collection=$this->getFileCollection();
        $this->clearFile($collection);

         return $this
            ->addMedia($file)
            ->toMediaCollection($collection);
    }
    public function clearFile($collection=null){
        if(!$collection)
            $collection=$this->getFileCollection();
        $this->getFirstMedia($collection)?$this->getMedia($collection)->each->delete():null;
        $this->clearMediaCollection($collection);
    }
}
