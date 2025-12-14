<?php


namespace App\Traits\Whatsapp;


use App\Enums\FileMediaType;
use App\Models\User;
use Illuminate\Support\Str;

trait InteractWithFile
{
   public function convert_to_url_image($image,User $user): ?string
   {
       if(filter_var($image,FILTER_VALIDATE_URL))
           return $image;
       else if(is_string($image)){
           $image=Str::after($image,'base64,');
           $user->saveImage($image,FileMediaType::BASE64);
           return $user->getImage();
       }
       else if(is_file($image)){
           $user->saveImage($image);
           return $user->getImage();
       }
       return null;
   }
    public function convert_to_url_video($video, User $user): ?string
    {
        if(filter_var($video,FILTER_VALIDATE_URL))
            return $video;
        else if(is_string($video)){
            $user->saveImage($video,FileMediaType::BASE64);
            return $user->getImage();
        }
        else if(is_file($video)){
            $user->saveImage($video);
            return $user->getImage();
        }
        return null;
    }
    public function convert_to_url_document($document, User $user)
    {
        if(filter_var($document,FILTER_VALIDATE_URL))
            return $document;
        else if(is_file($document)){
            $user->saveFile($document);
            return $user->getFileResource();
        }
        return null;
    }
}
