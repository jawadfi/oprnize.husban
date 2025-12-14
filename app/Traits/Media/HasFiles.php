<?php

namespace App\Traits\Media;


use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasFiles
{

    public function getAttachments($collection_name=null): MediaCollection
    {
        if(!$collection_name)
            $collection_name=$this->getFileCollection();
        return $this->getMedia($collection_name);
    }

    public function getFirstAttachment($collection_name=null):string|null
    {
        if(!$collection_name)
            $collection_name=$this->getFileCollection();
        return $this->getMedia($collection_name)->first()?->getFullUrl();
    }
    public function getFirstAttachmentResource($collection_name=null)
    {
        if(!$collection_name)
            $collection_name=$this->getFileCollection();
        $media=$this->getMedia($collection_name)->first();
        if(!$media)
            return null;
        return self::ResourceAttachment($media);
    }
    #[ArrayShape(['absolute_path' => "string", 'url' => "string", 'size' => "string", 'id' => "mixed", 'extension' => "string", 'date' => "mixed", 'name' => "mixed"])]
    private static function ResourceAttachment(Media $item): array
    {
        return [
//            'absolute_path'=>$item->getPath(),
            'url'=>$item->getFullUrl(),
            'size'=>$item->human_readable_size,
            'id'=>$item->id,
            'extension'=>$item->extension,
            'date'=>$item->created_at->format('M d Y'),
            'name'=>$item->name
        ];
    }
    public function getAttachmentsResource($collection_name=null): array
    {
        if(!$collection_name)
            $collection_name=$this->getFileCollection();
        return $this->getAttachments($collection_name)->transform(fn($media)=>self::ResourceAttachment($media))->toArray();
    }
    public function saveAttachment($file,$collection_name=null): Media
    {
        if(!$collection_name)
            $collection_name=$this->getFileCollection();
         return $this
            ->addMediaFromRequest($file)
            ->toMediaCollection($collection_name);
    }
    public function clearAttachment(){
        $this->getFirstMedia($this->getFileCollection())?$this->getMedia($this->getFileCollection())->each->delete():null;
        $this->clearMediaCollection($this->getFileCollection());
    }
    public function saveAttachments($files){
        foreach ($files as $file)
            $this->saveAttachment($file);
    }



//    public function

}
