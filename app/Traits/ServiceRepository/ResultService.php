<?php


namespace App\Traits\ServiceRepository;


use App\Enums\StatusCode;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ResultService
{
    private $data = null;
    private $message = null;
    private $code = null;
    private $errors = null;
    /**
     * @deprecated version
     */
    private $status = null;
    public function setPaginator(Paginator $paginator, ResourceCollection|array $resourceCollection)
    {
        return $this->setData($this->get_paginator($paginator, $resourceCollection));
    }
    public function get_paginator(Paginator $paginator, ResourceCollection|array $resourceCollection)
    {
        $total=$paginator->total();
        $per_page=$paginator->perPage();
        $pages=ceil($total/$per_page);
        $per_page=$per_page>$total?$total:$per_page;
        return  [
            'items' => $resourceCollection,
            'total' => $total,
            'next' => $paginator->nextPageUrl(),
            'previous' => $paginator->previousPageUrl(),
            'current_page' => $paginator->currentPage(),
            'number_pages' => $pages,
            'items_per_page' => $per_page
        ];
    }
    /**
     * set status
     * @param $status
     * @deprecated version
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * get status
     * @deprecated version
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }


    /**
     * set result output
     * @deprecated version
     * @param $result
     * @return $this
     */
    public function setResult($result)
    {
        $this->data = $result;

        return $this;
    }

    /**
     * get result
     * @deprecated version
     * @return null
     */
    public function getResult()
    {
        return $this->data;
    }

    /**
     * set data output
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * get data
     * @return null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * set message
     * @param $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * get message
     * @return null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * set code
     * @param $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * get code
     * @return null
     */
    public function getCode()
    {
        return $this->code;
    }


    /**
     * set errors
     * @param $error
     * @return $this
     */
    public function setError($error)
    {
        $this->errors = $error;
        return $this;
    }

    /**
     * get errors
     * @return array
     */
    public function getError()
    {
        return $this->errors;
    }

    /**
     * Exception Response
     *
     * @param Exception $exception
     * @return ResultService
     */
    public function exceptionResponse(Exception $exception,$code=500)
    {
        if ($exception instanceof QueryException) {
            if ($exception->errorInfo[1] == 1451) {
                return $this->setMessage('This data cannot be removed because it is still in use.')
                    ->setCode(400);
            }
        }
        if ($exception instanceof ModelNotFoundException) {
            if (!request()->expectsJson()) {
                return abort(404);
            }
            return $this->setMessage('Data not found')
                ->setCode(404);
        }
        $message = (object)[
            'exception' => 'Error',
            'error_message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace()
        ];

        return
            $this->setData(null)
                ->setMessage($exception->getMessage())
                ->setCode($code)
                ->setError($message);
    }

    /**
     * response to json
     * @return \Illuminate\Http\JsonResponse
     */
    public function toJson()
    {
        if(is_null($this->getCode())){
            $http_code = 200;

        }else{
            $http_code = $this->getCode();
        }

        $status=$this->getStatus();

        if(!$status)
            if( $http_code===200)
                $status=StatusCode::SUCCESS;
            else  if( $http_code===404)
                $status=StatusCode::NOT_FOUND;
            else
                $status=StatusCode::FAILED;

        if($this->getStatus() === true)
            $status=StatusCode::SUCCESS;

        return response()->json([
            'status' => $status,
            'code' => $http_code,
            'message' => $this->getMessage(),
            'data' => $this->getData(),
            'errors' => request('debug')?$this->getError():null,
        ],$http_code);
    }
}
