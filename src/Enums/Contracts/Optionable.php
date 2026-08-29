<?php

namespace SmartDato\CorreosShipping\Enums\Contracts;

use BackedEnum;

/**
 * A backed enum that can render itself as select options, so a UI can build a
 * dropdown from any of the SDK's enums without knowing which one it holds.
 */
interface Optionable extends BackedEnum
{
    /**
     * A human readable name for the case.
     */
    public function label(): string;

    /**
     * Value => label pairs, ready to hand to a select input.
     *
     * @return array<int|string, string>
     */
    public static function options(): array;
}
