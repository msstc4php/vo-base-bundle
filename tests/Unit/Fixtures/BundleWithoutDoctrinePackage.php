<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures;

use MSSTC4PHP\ValueObject\Bundle\Msstc4phpVoBaseBundle;
use Override;

/** Simulates msstc4php/vo-base-doctrine not being installed. */
final class BundleWithoutDoctrinePackage extends Msstc4phpVoBaseBundle
{
    // The alias is derived from the class name, so it must be pinned or the
    // subclass would look for configuration under its own name.
    protected string $extensionAlias = 'msstc4php_vo_base';

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
