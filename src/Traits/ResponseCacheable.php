<?php

namespace KieranFYI\Misc\Traits;

use DateTimeInterface;
use Illuminate\Support\Facades\Request;
use KieranFYI\Misc\Exceptions\CacheableException;
use KieranFYI\Misc\Http\Middleware\CacheableMiddleware;

trait ResponseCacheable
{
    /**
     * @throws CacheableException
     */
    private function check() {
        if (Request::method() === 'HEAD') {
            throw new CacheableException();
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function cache(string $key, mixed $value): static
    {
        CacheableMiddleware::set($key, $value);
        return $this;
    }

    /**
     * @param string $value
     * @throws CacheableException
     *
     * @return void
     */
    public function setEtag(string $value): void
    {
        $this->cache('etag', $value)->check();
    }

    /**
     * @param string $value
     * @throws CacheableException
     *
     * @return void
     */
    public function setLastModified(?DateTimeInterface $value): void
    {
        $this->cache('last_modified', $value)->public()->check();
    }

    /**
     * @param string $value
     * @throws CacheableException
     *
     * @return void
     */
    public function setMaxAge(mixed $value): void
    {
        $this->cache('max_age', $value)->check();
    }

    /**
     * @param string $value
     * @throws CacheableException
     *
     * @return void
     */
    public function setSharedMaxAge(mixed $value): void
    {
        $this->cache('s_maxage', $value)->check();
    }

    /**
     * @param bool $value
     * @return static
     */
    public function setStaleWhileRevalidate(bool $value): static
    {
        $this->cache('stale_while_revalidate', $value);
        return $this;
    }

    /**
     * @param bool $value
     * @return static
     */
    public function setStaleIfError(bool $value): static
    {
        $this->cache('stale_if_error', $value);
        return $this;
    }

    /**
     * @return static
     */
    public function public(): static
    {
        $this->cache('public', true);
        return $this;
    }

    /**
     * @return static
     */
    public function private(): static
    {
        $this->cache('private', true);
        return $this;
    }

}