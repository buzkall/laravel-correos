<?php

use SmartDato\CorreosShipping\Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__);

/**
 * Decode a JSON fixture from tests/Fixtures.
 *
 * @return array<string, mixed>
 */
function fixtureJson(string $path): array
{
    $contents = file_get_contents(__DIR__.'/Fixtures/'.$path);

    if ($contents === false) {
        throw new RuntimeException("Unable to read the fixture at tests/Fixtures/{$path}.");
    }

    /** @var array<string, mixed> $decoded */
    $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

    return $decoded;
}

/**
 * Narrow a value that may be a laravel-data Optional to its concrete type.
 *
 * @template T of object
 *
 * @param  class-string<T>  $class
 * @return T
 */
function present(mixed $value, string $class): object
{
    if (! $value instanceof $class) {
        throw new RuntimeException(sprintf('Expected an instance of %s, got %s.', $class, get_debug_type($value)));
    }

    return $value;
}
