<?php

declare(strict_types=1);

namespace Msstc4Symfony\VoBaseBridge\Test\Unit\Fixtures;

use Msstc4Symfony\VoBaseBridge\ValueObjectBridgeBundle;
use Override;

/** Simulates symfony/validator not being installed. */
final class BundleWithoutValidator extends ValueObjectBridgeBundle
{
    #[Override]
    protected function validatorAvailable(): bool
    {
        return false;
    }
}
