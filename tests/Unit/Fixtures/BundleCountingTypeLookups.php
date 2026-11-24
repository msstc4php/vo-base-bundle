<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures;

use MSSTC4PHP\ValueObject\Bundle\Msstc4phpVoBaseBundle;
use Override;

final class BundleCountingTypeLookups extends Msstc4phpVoBaseBundle
{
    public int $lookups = 0;

    protected string $extensionAlias = 'msstc4php_vo_base';

    #[Override]
    protected function ormTypes(): ?array
    {
        $this->lookups++;

        return parent::ormTypes();
    }

    #[Override]
    protected function odmTypes(): ?array
    {
        $this->lookups++;

        return parent::odmTypes();
    }
}
