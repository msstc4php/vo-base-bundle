<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures;

use MSSTC4PHP\ValueObject\Bundle\Msstc4phpVoBaseBundle;
use Override;

/** Simulates an install with DBAL only or MongoDB ODM only: the other branch's base type does not exist. */
final class BundleWithOnlyOneDoctrineStack extends Msstc4phpVoBaseBundle
{
    private const string MISSING_BASE = 'Doctrine\Missing\Types\Type';

    protected string $extensionAlias = 'msstc4php_vo_base';

    public function __construct(private readonly bool $withOrm)
    {
    }

    #[Override]
    protected function ormBase(): string
    {
        return $this->withOrm ? parent::ormBase() : self::MISSING_BASE;
    }

    #[Override]
    protected function odmBase(): string
    {
        return $this->withOrm ? self::MISSING_BASE : parent::odmBase();
    }
}
