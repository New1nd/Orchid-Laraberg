<?php
declare(strict_types=1);

namespace ProTrafficGroup\OrchidLaraberg;

use Orchid\Screen\Field;

class Laraberg extends Field
{
    /** @var string */
    protected $view = 'laraberg::field';

    /** @var array  */
    protected $attributes = [
        'options' => [],
    ];

    public static function make(?string $name = null): static
    {
        return (new static())
            ->name($name)
            ->setOptions(config('laraberg.options', []));
    }

    public function setOptions(array $options): static
    {
        $this->attributes['options'] = $options;

        return $this;
    }

    public function mergeOptions(array $options): static
    {
        $this->attributes['options'] = array_merge($this->attributes['options'], $options);

        return $this;
    }
}
