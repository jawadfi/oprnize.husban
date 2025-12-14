<?php

namespace App\Traits\Media;


use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasAttachments
{
    use InteractsWithMedia;


    public function getAttachments(): MediaCollection
    {
        return $this->getMedia($this->getAttachmentCollection());
    }

    public function getFirstAttachment():string|null
    {
        return $this->getMedia($this->getAttachmentCollection())->first()?->getFullUrl();
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
    public function getAttachmentsResource(): array
    {
        return $this->getAttachments()->transform(fn($media)=>self::ResourceAttachment($media))->toArray();
    }
    public function saveAttachment($file): Media
    {
         return $this
            ->addMedia($file)
            ->toMediaCollection($this->getAttachmentCollection());
    }
    public function clearAttachment(){
        $this->getFirstMedia($this->getAttachmentCollection())?$this->getMedia($this->getAttachmentCollection())->each->delete():null;
        $this->clearMediaCollection($this->getAttachmentCollection());
    }
    public function saveAttachments($files){
        foreach ($files as $file)
            $this->saveAttachment($file);
    }



//    public function

}
