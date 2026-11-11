<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Integration\Fixture;

use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use MSSTC4PHP\ValueObject\Network\Port;

final readonly class SignUp
{
    public function __construct(
        public EmailAddress $email,
        public ?Port $port = null,
    ) {
    }
}
