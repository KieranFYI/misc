<?php

namespace KieranFYI\Misc\Traits;

use Illuminate\Database\Eloquent\Model;
use TypeError;

/**
 * @property string $title
 * @property string $title_key
 * @property string $title_detailed
 * @mixin Model
 */
trait KeyedTitle
{
    public function initializeKeyedTitle(): void
    {
        array_push($this->appends, 'title', 'title_detailed');
    }

    /**
     * Get the policies defined on the provider.
     *
     * @return string
     */
    public function getTitleAttribute(): string
    {
        $title = null;
        if (property_exists($this, 'title_key')) {
            if (!is_string($this->title_key)) {
                throw new TypeError(self::class . '::getTitleAttribute(): Property ($title_key) must be of type string');
            }

            $title =  $this->getAttribute($this->title_key);
        }

        if (is_null($title)) {
            $title = $this->getKey();
        }

        return $title ?? 'Unknown';
    }


    /**
     * @return string|null
     */
    public function getTitleDetailedAttribute(): ?string
    {
        $parts = explode('\\', static::class);
        $className = array_pop($parts);

        $title = $this->title;

        if (!is_null($this->deleted_at)) {
            return $className . ': ' . $title . ' (Soft Deleted)';
        }

        return $className . ': ' . $title;
    }
}