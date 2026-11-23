<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures;

use MSSTC4PHP\ValueObject\Payload\ProvidesCodecs;
use Override;

final class RangeCodecs implements ProvidesCodecs
{
    #[Override]
    public static function all(): array
    {
        return [new RangeTextCodec()];
    }
}
