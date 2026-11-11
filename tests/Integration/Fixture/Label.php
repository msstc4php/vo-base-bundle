<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Integration\Fixture;

use MSSTC4PHP\ValueObject\Primitive\BoundedString;

final readonly class Label
{
    public function __construct(
        public BoundedString $name,
    ) {
    }
}
