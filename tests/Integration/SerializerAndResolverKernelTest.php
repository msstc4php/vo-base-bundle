<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Integration;

use MSSTC4PHP\ValueObject\Bundle\Test\Integration\Kernel\IntegrationKernel;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use Override;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * The real FrameworkBundle wiring: the normalizer must win over JsonSerializableNormalizer and the resolver over
 * RequestAttributeValueResolver, which would otherwise hand the raw string to the typed argument.
 */
#[CoversNothing]
final class SerializerAndResolverKernelTest extends TestCase
{
    private IntegrationKernel $kernel;

    #[Override]
    protected function setUp(): void
    {
        $this->kernel = new IntegrationKernel([], ['framework' => ['serializer' => ['enabled' => true]]], 'serializer-resolver');
        $this->kernel->boot();
    }

    // A debug kernel registers ErrorHandler on boot; early Symfony 7.4 leaves it installed after shutdown.
    #[Override]
    protected function tearDown(): void
    {
        $this->kernel->shutdown();
        \restore_exception_handler();
    }

    public function testTheFrameworkSerializerRoundTripsValueObjects(): void
    {
        $serializer = $this->service('serializer', SerializerInterface::class);

        self::assertSame('{"min":1,"max":5}', $serializer->serialize(new IntRange(1, 5), 'json'));
        self::assertEquals(EmailAddress::from('a@example.com'), $serializer->deserialize('"a@example.com"', EmailAddress::class, 'json'));
    }

    public function testRouteAttributesResolveIntoTypedArguments(): void
    {
        $resolver = $this->service('argument_resolver', ArgumentResolverInterface::class);
        $controller = static fn (EmailAddress $email, Port $port): string => $email . ':' . $port;

        $arguments = $resolver->getArguments(new Request(attributes: ['email' => 'a@example.com', 'port' => '8080']), $controller);

        self::assertEquals([EmailAddress::from('a@example.com'), Port::from(8080)], $arguments);
    }

    public function testAnInvalidRouteAttributeIsANotFound(): void
    {
        $resolver = $this->service('argument_resolver', ArgumentResolverInterface::class);

        $this->expectException(NotFoundHttpException::class);

        $resolver->getArguments(new Request(attributes: ['email' => 'nope']), static fn (EmailAddress $email): string => (string) $email);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $type
     *
     * @return T
     */
    private function service(string $id, string $type): object
    {
        $container = $this->kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $container);
        $service = $container->get($id);
        self::assertInstanceOf($type, $service);

        return $service;
    }
}
