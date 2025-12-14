<?php


namespace App\Traits\Media;


use Illuminate\Support\Str;
use Spatie\MediaLibrary\Conversions\Manipulations;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;

trait InteractsWithMediaExtended
{
    use InteractsWithMedia;

    public function registerMediaConversions(Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
    }

    public function addBase64Images(array|string $base64Images, string $collection_name, string $filename = null, string $extension = null)
    {
        $this->clearMediaCollection($collection_name);
        $this->getMedia($collection_name)->each->delete();

        if (is_string($base64Images))
            $base64Images = [$base64Images];

        foreach ($base64Images as $base64Image) {

            // Decode the base64 string
            $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));

            // Generate a unique filename if none is provided
            $filename = $filename ?? Str::uuid();
            $extension = $extension ?? 'png'; // Default to png if no extension

            // Construct the full filename
            $fullFilename = "{$filename}.{$extension}";

            // Add the media from base64
             $this->addMediaFromBase64($base64Image)
                ->usingName($filename) // Set the name for the media
                ->usingFileName($fullFilename) // Set the file name
                ->toMediaCollection($collection_name); // Store in the default collection
        }
    }
}
