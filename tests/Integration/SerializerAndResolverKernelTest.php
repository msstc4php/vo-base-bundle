<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Integration;

use MSSTC4PHP\ValueObject\Bundle\Test\Integration\Fixture\SignUp;
use MSSTC4PHP\ValueObject\Bundle\Test\Integration\Kernel\IntegrationKernel;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use Override;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestPayloadValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

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

    public function testMapRequestPayloadTurnsAnInvalidValueObjectIntoA422WithThePropertyPath(): void
    {
        self::assertEquals(
            [new SignUp(EmailAddress::from('a@example.com'), Port::from(8080))],
            $this->mapRequestPayload('{"email":"a@example.com","port":8080}'),
        );

        try {
            $this->mapRequestPayload('{"email":"not an email"}');
            self::fail('Expected a 422.');
        } catch (HttpException $exception) {
            self::assertSame(422, $exception->getStatusCode());
            $failure = $exception->getPrevious();
            self::assertInstanceOf(ValidationFailedException::class, $failure);
            self::assertSame('email', $failure->getViolations()->get(0)->getPropertyPath());
        }
    }

    /**
     * #[MapRequestPayload] arguments are placeholders until kernel.controller_arguments, so the listener runs here too.
     *
     * @return list<mixed>
     */
    private function mapRequestPayload(string $json): array
    {
        $request = Request::create('/sign-up', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: $json);
        $controller = static fn (#[MapRequestPayload] SignUp $input): string => (string) $input->email;
        $arguments = $this->service('argument_resolver', ArgumentResolverInterface::class)->getArguments($request, $controller);

        $event = new ControllerArgumentsEvent($this->kernel, $controller, $arguments, $request, HttpKernelInterface::MAIN_REQUEST);
        $this->service('argument_resolver.request_payload', RequestPayloadValueResolver::class)->onKernelControllerArguments($event);

        return array_values($event->getArguments());
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
