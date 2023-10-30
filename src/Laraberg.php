<?php
declare(strict_types=1);

namespace ProTrafficGroup\OrchidLaraberg\Laraberg;

use Orchid\Screen\Field;

class Laraberg extends Field
{
    /** @var string */
    protected $view = 'laraberg::field';

    /** @var array  */
    protected $attributes = [
        'options' => [],
    ];

    public static function make(?string $name = null): CKEditor
    {
        return (new static())
            ->name($name)
            ->setOptions(config('laraberg.options', []));
    }

    public function setOptions(array $options): Laraberg
    {
        $this->attributes['options'] = $options;

        return $this;
    }

    public function mergeOptions(array $options): Laraberg
    {
        $this->attributes['options'] = array_merge($this->attributes['options'], $options);

        return $this;
    }
}
