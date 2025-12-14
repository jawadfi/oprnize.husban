<?php

namespace App\Traits\Media;


use App\Enums\FileMediaType;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasLogo
{
    public function getDefault(): string
    {
        return asset('images/media-placeholder.png');
    }

    public function getPreview(): string
    {
        $media = $this->getFirstMedia($this->getLogoCollection());
        if ($media)
            return $media->getUrl('preview');

        return $this->getDefault();
    }

    public function getPath(): string
    {
        $media = $this->getFirstMedia($this->getLogoCollection());
        return $media->getPath();
    }

    public function getLogo($with_default = false, $conversation = null)
    {

        $media = $this->getFirstMedia($this->getLogoCollection());
        if ($media)
            return $conversation ? $media->getUrl($conversation) : $media->getFullUrl();
        if ($with_default)
            return $this->getDefault();
        return null;
    }

    public function getLogoPreview(): string
    {
        $media = $this->getFirstMedia($this->getLogoCollection());
        if ($media)
            return $media->getUrl('preview');

        return $this->getDefault();
    }

    #[ArrayShape(['thumbnail' => "string", 'preview' => "string"])] public function getLogoWithPreview(): array
    {
        $media = $this->getFirstMedia($this->getLogoCollection());
        if ($media) {
            return [
                'preview' => asset('storage/' . Str::after($media->getFullUrl(), 'storage/')),
                'thumbnail' => $media->getUrl('preview'),
            ];
        }

        return [
            'preview' => null,
            'thumbnail' => null,
        ];
    }

    public function getLogoResource(): array
    {
        $media = $this->getMedia($this->getLogoCollection());
        return $media->transform(fn(Media $item) => array_merge([
            'size' => $item->getHumanReadableSizeAttribute(),
            'extension' => $item->getExtensionAttribute(),
            'date' => $item->created_at->format('M d Y'),
            'name' => $item->name
        ], $this->getLogoWithPreview()))->first();
    }

    function fix_base_64_ext($model)
    {
        $media = $model->getFirstMedia($this->getLogoCollection());
        dd($media->mime_type);
        $correct_ext = Str::afterLast($media->mime_type, 'image/');
        $path_image = Str::afterLast($model->getLogo(), 'storage/');
        $file = Str::afterLast($path_image, '/');
        $file_name = Str::before($file, '.');
        $replaced_path_image = Str::replace($file, "$file_name.$correct_ext", $path_image);
        $rename_file = rename(public_path("storage/$path_image"), public_path("storage/$replaced_path_image"));
        $media->update(['file_name' => "$file_name.$correct_ext"]);
    }

    public function deleteLogo()
    {
        $this->getFirstMedia($this->getLogoCollection()) ? $this->getMedia($this->getLogoCollection())->each->delete() : null;
    }

    public function saveLogo($logo, $type = FileMediaType::REQUEST)
    {
        $model = $this;
        $model->getFirstMedia($this->getLogoCollection()) ? $model->getMedia($this->getLogoCollection())->each->delete() : null;
        $model->clearMediaCollection($this->getLogoCollection());

        if ($type === FileMediaType::BASE64) {
            $model->addMediaFromBase64($logo)->toMediaCollection($this->getLogoCollection());
            $this->fix_base_64_ext($model);
        } else if ($type === FileMediaType::ASSETS) {
            $model->addMediaFromDisk($logo)->toMediaCollection($this->getLogoCollection(), 'public');
        } else if ($type === FileMediaType::URL) {
            $model->addMediaFromUrl($logo)->toMediaCollection($this->getLogoCollection());
        } else {
            $model
                ->addMediaFromRequest($logo)
                ->toMediaCollection($this->getLogoCollection());
        }
    }
}
