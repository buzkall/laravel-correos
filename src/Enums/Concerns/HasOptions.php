<?php

namespace SmartDato\CorreosShipping\Enums\Concerns;

trait HasOptions
{
    /**
     * A human readable name for the case.
     */
    abstract public function label(): string;

    /**
     * Value => label pairs, ready to hand to a select input.
     *
     * @return array<int|string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
