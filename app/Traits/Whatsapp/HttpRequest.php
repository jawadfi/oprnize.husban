<?php


namespace App\Traits\Whatsapp;


use App\Exceptions\WhatsappException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

trait HttpRequest
{
    private function url(string $endpoint): string
    {
        return config('whatsapp.base_url').'/'.$endpoint;
    }

    /**
     * @param $endpoint
     * @param array $data
     * @return \Illuminate\Http\Client\Response
     * @throws WhatsappException
     */
    public function post($endpoint, array $data=[]): Response
    {
        try{
            $url = $this->url($endpoint);

            $response = Http::post($url, $data);

            if ($response->json('success') === true)
                return $response;
            else
                throw new WhatsappException($response->json('message'), $response->status());
        } catch (\Exception $exception) {
            throw new WhatsappException($exception->getMessage(), $exception->getCode());
    }
    }
    public function get($endpoint, array $parameter=[]): Response
    {
        try{
            $url = $this->url($endpoint);

            $response = Http::withQueryParameters($parameter)->get($url);

            if ($response->json('success') === true)
                return $response;
            else
                throw new WhatsappException($response->json('message'), $response->status());
        } catch (\Exception $exception) {
            throw new WhatsappException($exception->getMessage(), $exception->getCode());
        }
    }
    public function delete($endpoint, array $data=[]): Response
    {
        try{
            $url = $this->url($endpoint);

            $response = Http::delete($url, $data);

            if ($response->json('success') === true)
                return $response;
            else
                throw new WhatsappException($response->json('message'), $response->status());
        } catch (\Exception $exception) {
            throw new WhatsappException($exception->getMessage(), $exception->getCode());
        }
    }
}
