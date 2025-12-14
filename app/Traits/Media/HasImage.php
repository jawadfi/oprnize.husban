<?php

namespace App\Traits\Media;



use App\Enums\FileMediaType;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasImage
{
    public function getDefault(): string
    {
        return asset('images/media-placeholder.png');
    }

    public function getPreview(): string
    {
        $media=$this->getFirstMedia($this->getImageCollection());
        if($media)
            return $media->getUrl('preview');

        return $this->getDefault();
    }
    public function getPath(): string
    {
        $media=$this->getFirstMedia($this->getImageCollection());
        return $media->getPath();
    }
    public function getImage($with_default=false,$conversation=null)
    {

        $media=$this->getFirstMedia($this->getImageCollection());
        if($media)
            return $conversation?$media->getUrl($conversation):$media->getFullUrl();
        if($with_default)
            return $this->getDefault();
        return null;
    }
    public function getImagePreview(): string
    {
        $media=$this->getFirstMedia($this->getImageCollection());
        if($media)
            return $media->getUrl('preview');

        return $this->getDefault();
    }
    #[ArrayShape(['main' => "string", 'preview' => "string"])] public function getImageWithPreview(): array
    {
        $media=$this->getFirstMedia($this->getImageCollection());
        if($media){
            return [
                'preview'=>asset('storage/'.Str::after($media->getFullUrl(),'storage/')),
                'thumbnail'=>$media->getUrl('preview'),
                'watermark'=>$media->getUrl('watermark')
            ];
        }

        return [
            'preview'=>$this->getDefault(),
            'thumbnail'=>$this->getDefault(),
            'watermark'=>$this->getDefault(),
        ];
    }
    public function getImageResource(): array
    {
        $media = $this->getMedia($this->getImageCollection());
        return $media->transform(fn(Media $item) => array_merge([
            'size'=>$item->getHumanReadableSizeAttribute(),
            'extension'=>$item->getExtensionAttribute(),
            'date'=>$item->created_at->format('M d Y'),
            'name'=>$item->name
        ],$this->getImageWithPreview()))->first();
    }
    function fix_base_64_ext($model){
        $media=$model->getFirstMedia($this->getImageCollection());
        $correct_ext=Str::afterLast($media->mime_type,'image/');
        $path_image=Str::afterLast($model->getImage(),'storage/');
        $file=Str::afterLast($path_image,'/');
        $file_name=Str::before($file,'.');
        $replaced_path_image=Str::replace($file,"$file_name.$correct_ext",$path_image);
        $rename_file=rename(public_path("storage/$path_image"),public_path("storage/$replaced_path_image"));
        $media->update(['file_name'=>"$file_name.$correct_ext"]);
    }
    public function deleteImage(){
        $this->getFirstMedia($this->getImageCollection())?$this->getMedia($this->getImageCollection())->each->delete():null;
    }
    public function saveImage($image,$type=FileMediaType::REQUEST){
        $model=$this;
        $model->getFirstMedia($this->getImageCollection())?$model->getMedia($this->getImageCollection())->each->delete():null;
        $model->clearMediaCollection($this->getImageCollection());

        if($type===FileMediaType::BASE64){
            $model->addMediaFromBase64($image)->toMediaCollection($this->getImageCollection());
            $this->fix_base_64_ext($model);
        }else if($type===FileMediaType::ASSETS){
            $model->addMediaFromDisk($image)->toMediaCollection($this->getImageCollection(),'public');
        }
        else if($type===FileMediaType::URL){
            $model->addMediaFromUrl($image)->toMediaCollection($this->getImageCollection());
        }
        else{
            $model
                ->addMedia($image)
                ->toMediaCollection($this->getImageCollection());
        }
    }
}
