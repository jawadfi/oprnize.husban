<?php


namespace App\Helpers\Classes\Service;


use Illuminate\Support\Collection;
use LaravelEasyRepository\BaseService;

interface BaseServiceExtended extends BaseService
{
    public function selectOnly(array $columns,array $with=[]);
    public function last();
    public function first();
    function filters(array $domain=[],$limit=1,$fields=[]);
    public function paginate(int $limit=10,array $with=[],array $filter=[]):LengthAwarePaginator;
    public function createMany(array $data,array $merge=[]);
    public function all_with($with=[]);
    public function count():int;
}
