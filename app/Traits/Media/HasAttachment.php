<?php

namespace App\Traits\Media;


use App\Enums\FileMediaType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasAttachment
{
    use InteractsWithMedia;


    public function getAttachment(): MediaCollection
    {
        return $this->getMedia($this->getAttachmentCollection());
    }

    public function getFirstAttachment()
    {

        return $this->getMedia($this->getAttachmentCollection())->first();
    }
    public function getFirstAttachmentURL()
    {
        return $this->getMedia($this->getAttachmentCollection())?->first()?->getFullUrl();
    }
    #[ArrayShape(['absolute_path' => "string", 'url' => "string", 'size' => "string", 'id' => "mixed", 'extension' => "string", 'date' => "mixed", 'name' => "mixed"])]
    private static function ResourceAttachment($item): array
    {
        return [
//            'absolute_path'=>$item->getPath(),
            'url'=>$item->getFullUrl(),
            'size'=>$item->human_readable_size,
            'id'=>$item->id,
            'extension'=>$item->extension,
            'date'=>$item->created_at->format('M d Y'),
            'name'=>$item->file_name
        ];
    }
    public function getAttachmentsResource(): array
    {
        return $this->getAttachments()->transform(fn($media)=>self::ResourceAttachment($media))->toArray();
    }
    public function getAttachmentResource()
    {
        $media =$this->getFirstAttachment()
        ;
        return $media?self::ResourceAttachment($media):null;
    }
    public function saveAttachment($attachment,$type=FileMediaType::REQUEST,$file_name=null){
        $this->clearAttachment();

        $model=$this;
        $model->getFirstMedia($this->getAttachmentCollection())?$model->getMedia($this->getAttachmentCollection())->each->delete():null;
        $model->clearMediaCollection($this->getAttachmentCollection());

        if($type===FileMediaType::BASE64){
            $model->addMediaFromBase64($attachment)->toMediaCollection($this->getAttachmentCollection());
            $this->fix_base_64_ext($model,$file_name);
        }else if($type===FileMediaType::ASSETS){
            $model->addMediaFromDisk($attachment)->toMediaCollection($this->getAttachmentCollection(),'public');
        }
        else if($type===FileMediaType::URL){
            $model->addMediaFromUrl($attachment)->toMediaCollection($this->getAttachmentCollection());
        }
        else{
            $model
                ->addMedia($attachment)
                ->toMediaCollection($this->getAttachmentCollection());
        }
    }
    function fix_base_64_ext($model,$file_name=null){
        $media=$model->getFirstMedia($this->getAttachmentCollection());
        if(Str::contains($media->mime_type,'image/'))
        $correct_ext=Str::afterLast($media->mime_type,'image/');
        else if(Str::contains($media->mime_type,'application/pdf'))
            $correct_ext='pdf';
        $path_image=Str::afterLast($model->getFirstAttachmentURL(),'storage/');
        $file=Str::afterLast($path_image,'/');
        if(!$file_name)
            $file_name=Str::before($file,'.');
        $replaced_path_image=Str::replace($file,"$file_name.$correct_ext",$path_image);
        $rename_file=rename(public_path("storage/$path_image"),public_path("storage/$replaced_path_image"));
        DB::table('media')
            ->where('uuid',$media->uuid)
            ->update(['file_name'=>"$file_name.$correct_ext"]);
    }
    public function clearAttachment(){
        $this->getFirstMedia($this->getAttachmentCollection())?$this->getMedia($this->getAttachmentCollection())->each->delete():null;
        $this->clearMediaCollection($this->getAttachmentCollection());
    }

}
