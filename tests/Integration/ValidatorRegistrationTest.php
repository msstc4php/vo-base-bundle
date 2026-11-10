<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Integration;

use MSSTC4PHP\ValueObject\Bundle\Framework\Validator\ValidValueObjectValidator;
use MSSTC4PHP\ValueObject\Bundle\Msstc4phpVoBaseBundle;
use MSSTC4PHP\ValueObject\Bundle\Test\Support\TestConfigurator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

// The service definition lives in src/Resources/config, which no #[CoversClass] can list.
#[CoversNothing]
final class ValidatorRegistrationTest extends TestCase
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
}
