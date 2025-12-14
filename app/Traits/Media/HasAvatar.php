<?php

namespace App\Traits\Media;


use App\Enums\FileMediaType;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasAvatar
{
    public function getDefaultAvatar(): string
    {
        return asset('images/user-placeholder.png');
    }

    public function getAvatarPreview(): string
    {
        $media=$this->getFirstMedia($this->getAvatarCollection());
        if($media)
            return $media->getUrl('preview');

        return $this->getDefaultAvatar();
    }

    public function getAvatar(): string
    {
        $media=$this->getFirstMedia($this->getAvatarCollection());
        if($media)
            return $media->getFullUrl();
        return $this->getDefaultAvatar();
    }
    #[ArrayShape(['thumbnail' => "string", 'preview' => "string"])] public function getAvatarWithPreview(): array
    {
        $media=$this->getFirstMedia($this->getAvatarCollection());
        if($media){
            return [
                'preview'=>$this->getAvatar(),
                'thumbnail'=>$media->getUrl('preview')
            ];
        }

        return [
            'preview'=>$this->getDefaultAvatar(),
            'thumbnail'=>$this->getDefaultAvatar()
        ];
    }
    public function saveAvatar($image,$type=FileMediaType::REQUEST){
        $model=$this;
        $model->getFirstMedia($this->getAvatarCollection())?$model->getMedia($this->getAvatarCollection())->each->delete():null;
        $model->clearMediaCollection($this->getAvatarCollection());

        if($type===FileMediaType::BASE64){
            $filename = $filename ?? Str::uuid();
            $extension = $extension ?? 'png'; // Default to png if no extension

            // Construct the full filename
            $fullFilename = "{$filename}.{$extension}";

            // Add the media from base64
            return $this->addMediaFromBase64($image)
                ->usingName($filename) // Set the name for the media
                ->usingFileName($fullFilename) // Set the file name
                ->toMediaCollection($model->getAvatarCollection()); // Store in the default collection
        }else if($type===FileMediaType::ASSETS){
            $model->addMediaFromDisk($image)->toMediaCollection($this->getAvatarCollection(),'public');
        }
        else if($type===FileMediaType::URL){
            $model->addMediaFromUrl($image)->toMediaCollection($this->getAvatarCollection());
        }
        else{
            $model
                ->addMediaFromRequest($image)
                ->toMediaCollection($this->getAvatarCollection());
        }
    }


}
