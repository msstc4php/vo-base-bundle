<?php

declare(strict_types=1);

namespace Msstc4Symfony\VoBaseBridge\Test\Unit\Framework\Validator;

use Msstc4Symfony\VoBaseBridge\Framework\Validator\AssertIpv4;
use Msstc4Symfony\VoBaseBridge\Framework\Validator\ValidValueObjectValidator;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(AssertIpv4::class)]
#[UsesClass(ValidValueObjectValidator::class)]
final class AssertIpv4Test extends TestCase
{
    private ValidatorInterface $validator;

    #[Override]
    protected function setUp(): void
    {
        $this->validator = Validation::createValidator();
    }

    public function testAcceptsValidIpv4(): void
    {
        self::assertCount(0, $this->validator->validate('192.168.0.1', new AssertIpv4()));
    }

    public function testRejectsInvalidIpv4(): void
    {
        self::assertCount(1, $this->validator->validate('999.1.1.1', new AssertIpv4()));
    }
}
