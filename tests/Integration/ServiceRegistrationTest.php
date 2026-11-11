<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Integration;

use MSSTC4PHP\ValueObject\Bundle\Framework\HttpKernel\ValueObjectValueResolver;
use MSSTC4PHP\ValueObject\Bundle\Framework\Serializer\ValueObjectNormalizer;
use MSSTC4PHP\ValueObject\Bundle\Framework\Validator\ValidValueObjectValidator;
use MSSTC4PHP\ValueObject\Bundle\Msstc4phpVoBaseBundle;
use MSSTC4PHP\ValueObject\Bundle\Test\Support\TestConfigurator;
use MSSTC4PHP\ValueObject\Payload\PayloadCodec;
use MSSTC4PHP\ValueObject\Payload\ValueObjectMapper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

// The service definitions live in src/Resources/config, which no #[CoversClass] can list.
#[CoversNothing]
final class ServiceRegistrationTest extends TestCase
{
    /**
     * @return iterable<string, array{array<string, bool>}>
     */
    public static function enabledConfigs(): iterable
    {
        yield 'auto-detected' => [[]];
        yield 'demanded' => [['validator' => true]];
    }

    /**
     * @param array<string, bool> $config
     */
    #[DataProvider('enabledConfigs')]
    public function testValidatorServiceIsRegisteredAndTagged(array $config): void
    {
        $container = new ContainerBuilder();

        new Msstc4phpVoBaseBundle()->loadExtension($config, TestConfigurator::for($container), $container);

        self::assertTrue($container->hasDefinition(ValidValueObjectValidator::class));
        self::assertArrayHasKey(
            'validator.constraint_validator',
            $container->getDefinition(ValidValueObjectValidator::class)->getTags(),
        );
    }

    public function testSerializerAndValueResolverAreRegisteredByDefault(): void
    {
        $container = new ContainerBuilder();

        new Msstc4phpVoBaseBundle()->loadExtension([], TestConfigurator::for($container), $container);

        self::assertArrayHasKey('serializer.normalizer', $container->getDefinition(ValueObjectNormalizer::class)->getTags());
        self::assertSame(
            [['priority' => ValueObjectValueResolver::PRIORITY, 'name' => ValueObjectValueResolver::class]],
            $container->getDefinition(ValueObjectValueResolver::class)->getTag('controller.argument_value_resolver'),
        );
        self::assertTrue($container->hasDefinition(ValueObjectMapper::class));
        self::assertSame(
            [Msstc4phpVoBaseBundle::PAYLOAD_CODEC_TAG => [[]]],
            $container->getAutoconfiguredInstanceof()[PayloadCodec::class]->getTags(),
        );
    }

    public function testNoMapperWithoutAGroupThatNeedsIt(): void
    {
        $container = new ContainerBuilder();

        new Msstc4phpVoBaseBundle()->loadExtension(['serializer' => false, 'value_resolver' => false], TestConfigurator::for($container), $container);

        self::assertFalse($container->hasDefinition(ValueObjectMapper::class));
    }
}
