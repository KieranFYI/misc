<?php

namespace KieranFYI\Misc\Traits;

use Illuminate\Database\Eloquent\Model;
use KieranFYI\Logging\Traits\HasLoggingTrait;
use TypeError;

/**
 * @property string $title
 * @property string $title_detailed
 * @mixin Model
 */
trait KeyedTitle
{
    use HasLoggingTrait;

    /**
     * Get the policies defined on the provider.
     *
     * @return string
     */
    public function getTitleAttribute(): string
    {
        if (property_exists($this, 'title')) {
            if (!is_string($this->title)) {
                throw new TypeError(self::class . '::getTitleAttribute(): Property ($title) must be of type string');
            }

            return $this->title;
        }
        return $this->getKey();
    }


    /**
     * @return string|null
     */
    public function getTitleDetailedAttribute(): ?string
    {
        $parts = explode('\\', static::class);
        $className = array_pop($parts);

        $value = $this->getAttribute($this->title());

        if (is_null($value)) {
            return null;
        }

        if (!is_null($this->deleted_at)) {
            return $className . ': ' . $value . ' (Soft Deleted)';
        }

        return $className . ': ' . $value;
    }
}