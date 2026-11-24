<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures;

use ArrayObject;

final class SecondClashingRegistrar
{
    /**
     * @return array<non-empty-string, class-string>
     */
    public static function types(): array
    {
        return [
            'fixture_shared' => self::class,
            'fixture_second' => ArrayObject::class,
        ];
    }
}
