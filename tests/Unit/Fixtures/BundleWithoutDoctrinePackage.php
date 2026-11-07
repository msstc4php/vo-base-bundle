<?php

declare(strict_types=1);

namespace Msstc4Symfony\VoBaseBridge\Test\Unit\Fixtures;

use Msstc4Symfony\VoBaseBridge\ValueObjectBridgeBundle;
use Override;

/** Simulates the Doctrine bridge package not being installed. */
final class BundleWithoutDoctrinePackage extends ValueObjectBridgeBundle
{
    // The alias is derived from the class name, so it must be pinned or the
    // subclass would look for configuration under its own name.
    protected string $extensionAlias = 'value_object_bridge';

    #[Override]
    protected function ormTypes(): ?array
    {
        return null;
    }

    #[Override]
    protected function odmTypes(): ?array
    {
        return null;
    }
}
