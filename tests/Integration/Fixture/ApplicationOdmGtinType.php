<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Integration\Fixture;

use Doctrine\ODM\MongoDB\Types\StringType;

/** An application's own ODM type under a name a domain package also registers. */
final class ApplicationOdmGtinType extends StringType
{
}
