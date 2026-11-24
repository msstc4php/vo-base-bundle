<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Integration\Fixture;

use Doctrine\DBAL\Types\StringType;

/** An application's own type under a name a domain package also registers. */
final class ApplicationGtinType extends StringType
{
}
