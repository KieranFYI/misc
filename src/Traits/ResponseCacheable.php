<?php

namespace KieranFYI\Misc\Traits;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use KieranFYI\Misc\Exceptions\CacheableException;
use KieranFYI\Misc\Facades\Debugbar;
use KieranFYI\Misc\Http\Middleware\CacheableMiddleware;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

trait ResponseCacheable
{
    /**
     * @throws CacheableException
     */
    private function check()
    {
        Debugbar::stopMeasure('CacheableMiddleware');
        if (CacheableMiddleware::check()) {
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
     * @return void
     * @throws CacheableException
     *
     */
    public function setEtag(string $value): void
    {
        $this->cache('etag', $value)->check();
    }

    /**
     * @param Carbon $carbon
     * @return void
     * @throws CacheableException
     */
    public function setLastModified(Carbon $carbon): void
    {
        $this->cache('last_modified', $carbon)->check();
    }

    /**
     * @param string $value
     * @return void
     * @throws CacheableException
     *
     */
    public function setMaxAge(mixed $value): void
    {
        $this->cache('max_age', $value)->check();
    }

    /**
     * @param string $value
     * @return void
     * @throws CacheableException
     *
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
        return $this->cache('stale_while_revalidate', $value);
    }

    /**
     * @param bool $value
     * @return static
     */
    public function setStaleIfError(bool $value): static
    {
        return $this->cache('stale_if_error', $value);
    }

    /**
     * @return static
     */
    public function public(): static
    {
        return $this->cache('public', true);
    }

    /**
     * @return static
     */
    public function private(): static
    {
        return $this->cache('private', true);
    }

    /**
     * @param Carbon|null $value
     * @throws CacheableException
     */
    public function view(?Carbon $value = null): void
    {
        if (is_null($value)) {
            return;
        }
        $this->setLastModified($value);
    }


}