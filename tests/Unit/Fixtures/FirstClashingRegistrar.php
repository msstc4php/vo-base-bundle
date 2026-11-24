<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures;

use ArrayObject;
use MSSTC4PHP\ValueObject\Doctrine\Orm\EmailAddressType;

final class FirstClashingRegistrar
{
    /**
     * @return array<non-empty-string, class-string>
     */
    public static function types(): array
    {
        return [
            EmailAddressType::NAME => self::class,
            'fixture_shared' => self::class,
            'fixture_first' => ArrayObject::class,
        ];
    }
}
