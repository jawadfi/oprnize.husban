<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

trait Responsible
{
    public function returnWithDataOnly($data = null) {
        return response()->json($data);
    }
    public function returnWithPaginator(string $message,Paginator $paginator,ResourceCollection|array $resourceCollection,array $merge=[]): JsonResponse
    {
        $total=$paginator->total();
        $per_page=$paginator->perPage();
        $pages=ceil($total/$per_page);
        $per_page=$per_page>$total?$total:$per_page;
        return $this->returnWithSuccess(
            [
                ...$merge,
                'items'=>$resourceCollection,
                'total'=>$total,
                'next'=>$paginator->nextPageUrl(),
                'previous'=>$paginator->previousPageUrl(),
                'current_page'=>$paginator->currentPage(),
                'number_pages'=>$pages,
                'items_per_page'=>$per_page
            ],
            $message
        );
    }

    public function returnWithField(string $message, $data = null, $code = 500, $status = 'failed'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'status' => $status,
            'code' => $code
        ], $code);
    }
    public function returnWithFailed($data=null ,string $message="Failed", $code = 500, $status = 'failed'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'status' => $status,
            'code' => $code
        ]);
    }

    public function returnWithSuccess($data = null,string $message='Successfully Get Data', $code = 200, $status = 'success'): JsonResponse
    {
        if($message === 'Successfully Get Data')
            $message = __('mobile.Successfully Get Data');
        return response()->json([
            'message' => $message,
            'data' => $data,
            'status' => $status,
            'code' => $code
        ], $code);
    }

    public function returnWithModel(string $message, Model|JsonResource $data = null, $code = 200, $status = 'success'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'status' => $status,
            'code' => $code
        ], $code);
    }

    public function returnWithCollection(string $message, Collection|JsonResource $data = null, $code = 200, $status = 'success'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'status' => $status,
            'code' => $code
        ], $code);
    }

    public function returnWithErrors(string $message='Error', $errors = null, $code = 200, $status = 'failed'): JsonResponse
    {
        return response()->json([
            'errors' => $errors,
            'message' => $message,
            'status' => $status,
            'code' => $code

        ]);
    }
    public function returnWithNotFound(string $message='NotFound', $errors = null, $code = 404, $status = 'failed'): JsonResponse
    {
        return response()->json([
            'errors' => $errors,
            'message' => $message,
            'status' => $status,
            'code' => $code

        ]);
    }
//    Adveritsimint
}
