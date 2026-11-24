<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures;

use MSSTC4PHP\ValueObject\Bundle\Msstc4phpVoBaseBundle;
use Override;

/** Simulates none of the domain Doctrine packages being installed: their registrars do not exist. */
final class BundleWithoutDomainPackages extends Msstc4phpVoBaseBundle
{
    protected string $extensionAlias = 'msstc4php_vo_base';

    #[Override]
    protected function domainOrmRegistrars(): array
    {
        return ['MSSTC4PHP\ValueObject\Missing\Doctrine\Orm\TypeRegistrar'];
    }

    #[Override]
    protected function domainOdmRegistrars(): array
    {
        return ['MSSTC4PHP\ValueObject\Missing\Doctrine\Odm\TypeRegistrar'];
    }
}
