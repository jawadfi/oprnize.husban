<?php


namespace App\Helpers\Classes\Media;


use App\Helpers\Classes\Interfaces\IMedialPath;
use App\Models\ELecture;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

class PathGenerator extends DefaultPathGenerator
{
public function getPath(Media $media): string
{
//    if($media->model instanceof IMedialPath)
//        return $media->model->getPath();

    return parent::getPath($media);
}
}
