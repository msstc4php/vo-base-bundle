<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures;

use MSSTC4PHP\ValueObject\Bundle\Msstc4phpVoBaseBundle;
use Override;

/** Simulates symfony/serializer not being installed. */
final class BundleWithoutSerializer extends Msstc4phpVoBaseBundle
{
    #[Override]
    protected function serializerAvailable(): bool
    {
        return false;
    }
}
