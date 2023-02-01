<?php

namespace KieranFYI\Misc\Traits;

use Exception;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

trait HTTPClientTrait
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
     * @return HTTPRequestTrait
     */
    public function userAgent(string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * @param int $timeout
     * @return HTTPRequestTrait
     */
    public function timeout(int $timeout): static
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @param string $auth
     * @return HTTPRequestTrait
     */
    public function auth(string $auth): static
    {
        $this->auth = $auth;
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
    private function client(bool $json = true, array $options = []): PendingRequest
    {
        $headers = [
            'accept-encoding' => 'gzip, deflate',
        ];

        if (!empty($this->userAgent)) {
            $headers['User-Agent'] = $this->userAgent;
        }

        if ($json) {
            $headers['Accept'] = 'application/json';
        }

        if ($this->isAuthed()) {
            $headers['Authorization'] = $this->auth;
        }

        return Http::withOptions(array_merge($options, [
            RequestOptions::TIMEOUT => $this->timeout,
            RequestOptions::CONNECT_TIMEOUT => $this->timeout,
            RequestOptions::ALLOW_REDIRECTS => true,
            RequestOptions::HEADERS => $headers,
            RequestOptions::HTTP_ERRORS => false,
        ]));
    }
}
