<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit\Framework\HttpKernel;

use MSSTC4PHP\ValueObject\Bundle\Framework\HttpKernel\ValueObjectValueResolver;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObject\Payload\ValueObjectMapper;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use MSSTC4PHP\ValueObject\Primitive\Percentage;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[CoversClass(ValueObjectValueResolver::class)]
final class ValueObjectValueResolverTest extends TestCase
{
    private ValueObjectValueResolver $resolver;

    #[Override]
    protected function setUp(): void
    {
        $this->resolver = new ValueObjectValueResolver(new ValueObjectMapper());
    }

    public function testRouteAttributesBecomeValueObjects(): void
    {
        self::assertEquals([EmailAddress::from('a@example.com')], $this->resolve(['email' => 'a@example.com'], 'email', EmailAddress::class));
        self::assertEquals([Port::from(8080)], $this->resolve(['port' => '8080'], 'port', Port::class));
        self::assertEquals([Percentage::from(12.5)], $this->resolve(['rate' => '12.5'], 'rate', Percentage::class));
    }

    public function testAnInvalidAttributeIsANotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Could not resolve the "' . EmailAddress::class . ' $email" controller argument');

        $this->resolve(['email' => 'nope'], 'email', EmailAddress::class);
    }

    public function testNullAndAnAlreadyResolvedValuePassThrough(): void
    {
        $email = EmailAddress::from('a@example.com');

        self::assertSame([null], $this->resolve(['email' => null], 'email', EmailAddress::class));
        self::assertSame([$email], $this->resolve(['email' => $email], 'email', EmailAddress::class));
    }

    public function testArgumentsItDoesNotOwnAreLeftToOtherResolvers(): void
    {
        self::assertSame([], $this->resolve([], 'email', EmailAddress::class), 'no such route attribute');
        self::assertSame([], $this->resolve(['code' => 'DE'], 'code', CountryCode::class), 'enums belong to BackedEnumValueResolver');
        self::assertSame([], $this->resolve(['range' => '1-2'], 'range', IntRange::class), 'composites cannot come from a route');
        self::assertSame([], $this->resolve(['id' => '1'], 'id', 'int'), 'builtin type');
        self::assertSame([], $this->resolve(['id' => '1'], 'id', null), 'untyped');
        self::assertSame([], $this->resolve(['email' => 'a@example.com'], 'email', EmailAddress::class, true), 'variadic');
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return list<object|null>
     */
    private function resolve(array $attributes, string $name, ?string $type, bool $variadic = false): array
    {
        $request = new Request(attributes: $attributes);

        return [...$this->resolver->resolve($request, new ArgumentMetadata($name, $type, $variadic, false, null))];
    }
}
