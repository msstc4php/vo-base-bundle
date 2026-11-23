<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Integration;

use MSSTC4PHP\ValueObject\Bundle\Test\Integration\Kernel\IntegrationKernel;
use MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures\RangeCodecs;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Serializer\SerializerInterface;

// The mapper definition lives in src/Resources/config, which no #[CoversClass] can list.
#[CoversNothing]
final class CodecProvidersKernelTest extends TestCase
{
    public function testAProvidersCodecsReachTheSerializer(): void
    {
        $kernel = new IntegrationKernel([], [
            'framework' => ['serializer' => ['enabled' => true]],
            'msstc4php_vo_base' => ['codec_providers' => [RangeCodecs::class]],
        ], 'codec-providers');
        $kernel->boot();

        try {
            $container = $kernel->getContainer()->get('test.service_container');
            self::assertInstanceOf(ContainerInterface::class, $container);
            $serializer = $container->get('serializer');
            self::assertInstanceOf(SerializerInterface::class, $serializer);

            self::assertSame('{"range":"1..5"}', $serializer->serialize(new IntRange(1, 5), 'json'));
        } finally {
            $kernel->shutdown();
            \restore_exception_handler();
        }
    }

    public function testAClassThatProvidesNoCodecsIsAConfigurationError(): void
    {
        $kernel = new IntegrationKernel([], ['msstc4php_vo_base' => ['codec_providers' => [stdClass::class]]], 'codec-providers-invalid');

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('does not implement MSSTC4PHP\ValueObject\Payload\ProvidesCodecs.');

        try {
            $kernel->boot();
        } finally {
            \restore_exception_handler();
        }
    }
}
