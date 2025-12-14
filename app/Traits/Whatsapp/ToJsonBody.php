<?php


namespace App\Traits\Whatsapp;


use JetBrains\PhpStorm\ArrayShape;

trait ToJsonBody
{

    #[ArrayShape(['receiver' => "", 'message' => "array"])]
    static public function TextMessageJson($receiver, $text): array
    {
       return [
           'receiver'=>$receiver,
           'message'=>['text'=>$text]
       ];
    }

    static public function ImageMessageJson($receiver,$image_url,$caption=""): array
    {
        return [
            'receiver'=>$receiver,
            'message'=>[
                "image"=>[
                    'url'=>$image_url
                ],'caption'=>$caption
            ]
        ];
    }

    static public function ViedoMessageJson($receiver, $video_url, $caption=""): array
    {
        return [
            'receiver'=>$receiver,
            'message'=>[
                "video"=>[
                    'url'=>$video_url
                ],'caption'=>$caption
            ]
        ];
    }
    static public function DocumentMessageJson($receiver,$document_url,$mimetype,$fileName): array
    {
        return [
            'receiver'=>$receiver,
            'message'=>[
                "document"=>[
                    'url'=>$document_url
                ],
                'mimetype'=>$mimetype,
                'fileName'=>$fileName
            ]
        ];
    }

}
