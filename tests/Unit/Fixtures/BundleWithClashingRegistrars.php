<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures;

use MSSTC4PHP\ValueObject\Bundle\Msstc4phpVoBaseBundle;
use Override;

final class BundleWithClashingRegistrars extends Msstc4phpVoBaseBundle
{
    protected string $extensionAlias = 'msstc4php_vo_base';

    #[Override]
    protected function domainOrmRegistrars(): array
    {
        return [FirstClashingRegistrar::class, SecondClashingRegistrar::class];
    }

    #[Override]
    protected function domainOdmRegistrars(): array
    {
        return [FirstClashingRegistrar::class, SecondClashingRegistrar::class];
    }
}
