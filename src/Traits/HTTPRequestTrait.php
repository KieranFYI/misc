<?php

namespace KieranFYI\Misc\Traits;

use Exception;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\Response;

trait HTTPRequestTrait
{
    use HTTPClientTrait;

    /**
     * @param string $url
     * @param null $query
     * @return Response
     * @throws Exception
     */
    protected function get(string $url, $query = null): Response
    {
        return $this->client()->get($url, $query);
    }

    /**
     * @param string $url
     * @param array $data
     * @param bool $json
     * @return Response
     * @throws Exception
     */
    protected function post(string $url, array $data = [], bool $json = true): Response
    {
        $client = $this->client();
        if ($json) {
            $client->bodyFormat(RequestOptions::JSON);
        } else {
            $client->bodyFormat(RequestOptions::FORM_PARAMS);
        }
        return $client->post($url, $data);
    }

    /**
     * @param string $url
     * @param string $file
     * @return Response
     * @throws Exception
     */
    protected function download(string $url, string $file): Response
    {
        return $this->client(false)->send('GET', $url, ['sink' => $file]);
    }
}
