<?php

namespace KieranFYI\Misc\Traits;

use Exception;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use KieranFYI\Misc\Helpers\UserAgent;

trait HTTPRequestTrait
{
    /**
     * @var UserAgent|null
     */
    private static ?UserAgent $userAgent = null;

    /**
     * @var string|null
     */
    private ?string $customUserAgent = null;

    /**
     * @var string|null
     */
    private ?string $auth = null;

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
        if (is_null(static::$userAgent)) {
            static::$userAgent = new UserAgent();
        }

        $headers = [
            'User-Agent' => $customUserAgent ?? static::$userAgent->generate(),
            'accept-encoding' => 'gzip, deflate',
        ];

        if ($json) {
            $headers['Accept'] = 'application/json';
        }

        if ($this->isAuthed()) {
            $headers['Authorization'] = $this->auth;
        }

        return Http::withOptions([
            RequestOptions::TIMEOUT => 20,
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
