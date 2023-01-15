<?php

namespace KieranFYI\Misc\Traits;

use Exception;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

trait HTTPRequestTrait
{
    /**
     * @var string|null
     */
    private ?string $userAgent = null;

    /**
     * @var string|null
     */
    private ?string $auth = null;

    /**
     * @var int
     */
    private int $timeout = 20;

    /**
     * @param string $userAgent
     */
    public function userAgent(string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * @param int $timeout
     */
    public function timeout(int $timeout): static
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthed(): bool
    {
        return !is_null($this->auth);
    }

    /**
     * @param bool $json
     * @return PendingRequest
     * @throws Exception
     */
    private function client(bool $json = true): PendingRequest
    {
        $headers = [
            'accept-encoding' => 'gzip, deflate',
        ];

        if (is_null($this->userAgent)) {
            $headers['User-Agent'] = $this->userAgent;
        }

        if ($json) {
            $headers['Accept'] = 'application/json';
        }

        if ($this->isAuthed()) {
            $headers['Authorization'] = $this->auth;
        }

        return Http::withOptions([
            RequestOptions::TIMEOUT => $this->timeout,
            RequestOptions::CONNECT_TIMEOUT => $this->timeout,
            RequestOptions::ALLOW_REDIRECTS => true,
            RequestOptions::VERIFY => false,
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYSTATUS => false,
                CURLOPT_PROXY_SSL_VERIFYPEER => false,
                CURLOPT_PROXY_SSL_VERIFYHOST => false,
            ],
            RequestOptions::HEADERS => $headers
        ]);
    }

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
