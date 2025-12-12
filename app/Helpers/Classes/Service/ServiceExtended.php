<?php


namespace App\Helpers\Classes\Service;


use App\Classes\Interfaces\ISetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LaravelEasyRepository\Service;

class ServiceExtended extends Service
{
    public function selectOnly(array $columns, array $with = [])
    {
        return $this->mainRepository->selectOnly($columns, $with);
    }

    public function filters(array $domain = [], $limit = 1, $fields = [])
    {
        return $this->mainRepository->filters($domain, $limit, $fields);
    }



    public function create($data)
    {
        return $this->mainRepository->create($data);
    }

    public function last()
    {
        return $this->mainRepository->last();
    }
    public function first()
    {
        return $this->mainRepository->first();
    }
    public function updateSetting($id, $payload)
    {
        /** @var ISetting $model */
        $model = $this->find($id);
        return $model
            ->setting()
            ->updateOrCreate([], ['payload' => array_merge($model?->setting->payload ?? [], $payload)]);
    }

    public function getModel(Model|int $model)
    {
        if (is_numeric($model))
            return $this->find($model);
        return $model;
    }
    public function paginate(int $limit = 10, array $with = [], array $filter = []): LengthAwarePaginator
    {
        return $this->mainRepository->paginate($limit, $with, $filter);
    }

    public function createMany(array $data, array $merge = [])
    {
        try {
            foreach ($data as $record)
                $this->create(array_merge($record, $merge));
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
    }

    public function all_with($with = [])
    {
        return $this->mainRepository->all_with($with);
    }
    public function all($filter = null)
    {
        return $this->mainRepository->all($filter);
    }

    public function count(): int

    {
        return $this->mainRepository->count();
    }
}
