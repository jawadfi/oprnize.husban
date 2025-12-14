<?php

namespace App\Traits\Media;


use App\Enums\FileMediaType;
use App\Helpers\Classes\Interfaces\IGallery;
use App\Helpers\Helpers;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasGallery
{
    #[ArrayShape(['main' => "string", 'preview' => "string"])]
    public function getGalleryWithPreview(): array
    {
        return $this->getMedia($this->getGalleryCollection())->map(fn($media)=>[
            'preview' => Helpers::getFullUrlImage($media->getFullUrl()),
            'thumbnail' => $media->getUrl('preview'),
            'id'=>$media->id,
        ])->toArray();
    }
    public function getGallery(): array
    {
        return $this->getMedia($this->getGalleryCollection())->map(fn($media)=>$media->getFullUrl())->toArray();
    }
    public function getFirstGalleryWithPreview(): ?array
    {
        /** @var Media $media */
        $media = $this->getMedia($this->getGalleryCollection())->first();

        if($media){
            return [
                'preview' => Helpers::getFullUrlImage($media->getFullUrl()),
                'thumbnail' => $media->getUrl('preview'),
                'id'=>$media->id,
            ];
        }

        return null;
    }
    public function getFirstGallery(): ?string
    {
        /** @var Media $media */
        $media = $this->getMedia($this->getGalleryCollection())->first();

        if($media){
            return $media->getFullUrl();
        }

        return null;
    }
    public function saveGallery(array $images,$type=FileMediaType::REQUEST){
        /** @var IGallery|HasMedia $model */
        $model=$this;
        $model->clearMediaCollection($model->getGalleryCollection());
        $model->getMedia($model->getGalleryCollection())->each->delete();

        foreach ($images as $image){
            if($type===FileMediaType::BASE64){
                $model->addMediaFromBase64($image)->toMediaCollection($this->getGalleryCollection());
            }else if($type===FileMediaType::ASSETS){
                $model->addMediaFromDisk($image)->toMediaCollection($this->getGalleryCollection(),'public');
            }
            else if($type===FileMediaType::URL){
                $model->addMediaFromUrl($image)->toMediaCollection($this->getGalleryCollection());
            }
            else{
                $model
                    ->addMedia($image->getRealPath())
                    ->usingName($image->getClientOriginalName())
                    ->toMediaCollection($this->getGalleryCollection());
            }
        }
    }
    public function saveGalleryAsRequest(string $key,$type=FileMediaType::REQUEST){
        /** @var IGallery|HasMedia $model */
        $model=$this;
        $model->clearMediaCollection($model->getGalleryCollection());
        $model->getMedia($model->getGalleryCollection())->each->delete();
        foreach (request()->file($key) as $image){
            if($type===FileMediaType::BASE64){
                $model->addMediaFromBase64($image)->toMediaCollection($this->getGalleryCollection());
            }else if($type===FileMediaType::ASSETS){
                $model->addMediaFromDisk($image)->toMediaCollection($this->getGalleryCollection(),'public');
            }
            else if($type===FileMediaType::URL){
                $model->addMediaFromUrl($image)->toMediaCollection($this->getGalleryCollection());
            }
            else{
                $model
                    ->addMedia($image)
                    ->toMediaCollection($this->getGalleryCollection());
            }
        }
    }


}
