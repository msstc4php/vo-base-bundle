<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit;

use Doctrine\DBAL\Types\Type;
use LogicException;
use MSSTC4PHP\ValueObject\Bundle\Framework\HttpKernel\ValueObjectValueResolver;
use MSSTC4PHP\ValueObject\Bundle\Framework\Serializer\ValueObjectNormalizer;
use MSSTC4PHP\ValueObject\Bundle\Framework\Validator\ValidValueObjectValidator;
use MSSTC4PHP\ValueObject\Bundle\Msstc4phpVoBaseBundle;
use MSSTC4PHP\ValueObject\Bundle\Test\Support\TestConfigurator;
use MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures\BundleWithClashingRegistrars;
use MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures\BundleWithoutDoctrinePackage;
use MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures\BundleWithoutDomainPackages;
use MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures\BundleWithoutSerializer;
use MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures\BundleWithoutValidator;
use MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures\FirstClashingRegistrar;
use MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures\SecondClashingRegistrar;
use MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures\StubExtension;
use MSSTC4PHP\ValueObject\Doctrine\Odm\TypeRegistrar as OdmRegistrar;
use MSSTC4PHP\ValueObject\Doctrine\Orm\EmailAddressType;
use MSSTC4PHP\ValueObject\Doctrine\Orm\TypeRegistrar as OrmRegistrar;
use MSSTC4PHP\ValueObject\Documents\Doctrine\Odm\TypeRegistrar as DocumentsOdmRegistrar;
use MSSTC4PHP\ValueObject\Documents\Doctrine\Orm\TypeRegistrar as DocumentsOrmRegistrar;
use MSSTC4PHP\ValueObject\Financial\Doctrine\Odm\TypeRegistrar as FinancialOdmRegistrar;
use MSSTC4PHP\ValueObject\Financial\Doctrine\Orm\TypeRegistrar as FinancialOrmRegistrar;
use MSSTC4PHP\ValueObject\Gs1\Doctrine\Odm\TypeRegistrar as Gs1OdmRegistrar;
use MSSTC4PHP\ValueObject\Gs1\Doctrine\Orm\TypeRegistrar as Gs1OrmRegistrar;
use MSSTC4PHP\ValueObject\Identifiers\Doctrine\Odm\TypeRegistrar as IdentifiersOdmRegistrar;
use MSSTC4PHP\ValueObject\Identifiers\Doctrine\Orm\TypeRegistrar as IdentifiersOrmRegistrar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

#[CoversClass(Msstc4phpVoBaseBundle::class)]
final class Msstc4phpVoBaseBundleTest extends TestCase
{
    private const array DOMAIN_ORM_REGISTRARS = [
        Gs1OrmRegistrar::class,
        IdentifiersOrmRegistrar::class,
        DocumentsOrmRegistrar::class,
        FinancialOrmRegistrar::class,
    ];

    private const array DOMAIN_ODM_REGISTRARS = [
        Gs1OdmRegistrar::class,
        IdentifiersOdmRegistrar::class,
        DocumentsOdmRegistrar::class,
        FinancialOdmRegistrar::class,
    ];

    public function testExtensionAliasIsDerivedFromTheBundleName(): void
    {
        $extension = new Msstc4phpVoBaseBundle()->getContainerExtension();

        self::assertNotNull($extension);
        self::assertSame('msstc4php_vo_base', $extension->getAlias());
    }

    public function testOrmTypesArePrependedWhenBothPackageAndExtensionArePresent(): void
    {
        $this->requireDoctrinePackage();

        $container = $this->containerWith(['doctrine']);

        new Msstc4phpVoBaseBundle()->prependExtension($this->configurator($container), $container);

        $prepended = $container->getExtensionConfig('doctrine');
        self::assertSame([['dbal' => ['types' => $this->installedTypes(OrmRegistrar::types(), self::DOMAIN_ORM_REGISTRARS)]]], $prepended);
    }

    public function testOdmTypesArePrependedIntoTheirOwnExtension(): void
    {
        $this->requireDoctrinePackage();

        $container = $this->containerWith(['doctrine_mongodb']);

        new Msstc4phpVoBaseBundle()->prependExtension($this->configurator($container), $container);

        self::assertSame([['types' => $this->installedTypes(OdmRegistrar::types(), self::DOMAIN_ODM_REGISTRARS)]], $container->getExtensionConfig('doctrine_mongodb'));
    }

    public function testDomainTypesJoinBothMaps(): void
    {
        $this->requireDomainPackages();

        $container = $this->containerWith(['doctrine', 'doctrine_mongodb']);

        new Msstc4phpVoBaseBundle()->prependExtension($this->configurator($container), $container);

        $orm = $this->typeMap($container->getExtensionConfig('doctrine')[0] ?? null, 'dbal');
        $odm = $this->typeMap($container->getExtensionConfig('doctrine_mongodb')[0] ?? null, null);

        foreach (self::DOMAIN_ORM_REGISTRARS as $registrar) {
            self::assertSame([], \array_diff_assoc($registrar::types(), $orm), $registrar);
        }

        foreach (self::DOMAIN_ODM_REGISTRARS as $registrar) {
            self::assertSame([], \array_diff_assoc($registrar::types(), $odm), $registrar);
        }

        $expected = \count(OrmRegistrar::types()) + \array_sum(\array_map(static fn (string $r): int => \count($r::types()), self::DOMAIN_ORM_REGISTRARS));
        self::assertCount($expected, $orm, 'No two packages may share a type name.');
    }

    public function testDomainTypesCanBeSwitchedOff(): void
    {
        $this->requireDoctrinePackage();

        $bundle = new Msstc4phpVoBaseBundle();
        $container = $this->containerWith(['doctrine', 'doctrine_mongodb']);
        $this->loadBlocks($bundle, $container, [['domain_types' => false]]);

        $bundle->prependExtension($this->configurator($container), $container);

        self::assertSame([['dbal' => ['types' => OrmRegistrar::types()]]], $container->getExtensionConfig('doctrine'));
        self::assertSame([['types' => OdmRegistrar::types()]], $container->getExtensionConfig('doctrine_mongodb'));
    }

    public function testAbsentDomainPackagesAddNothingAndRaiseNothing(): void
    {
        $this->requireDoctrinePackage();

        $container = $this->containerWith(['doctrine', 'doctrine_mongodb']);

        new BundleWithoutDomainPackages()->prependExtension($this->configurator($container), $container);

        self::assertSame([['dbal' => ['types' => OrmRegistrar::types()]]], $container->getExtensionConfig('doctrine'));
        self::assertSame([['types' => OdmRegistrar::types()]], $container->getExtensionConfig('doctrine_mongodb'));
    }

    public function testDomainTypesNeedTheBaseDoctrinePackage(): void
    {
        $bundle = new BundleWithoutDoctrinePackage();
        $container = $this->containerWith(['doctrine', 'doctrine_mongodb']);
        $this->loadBlocks($bundle, $container, [['domain_types' => true]]);

        $bundle->prependExtension($this->configurator($container), $container);

        self::assertSame([], $container->getExtensionConfig('doctrine'));
        self::assertSame([], $container->getExtensionConfig('doctrine_mongodb'));
    }

    public function testOnANameClashTheEarlierRegistrarKeepsTheName(): void
    {
        $this->requireDoctrinePackage();

        $container = $this->containerWith(['doctrine']);

        new BundleWithClashingRegistrars()->prependExtension($this->configurator($container), $container);

        $orm = $this->typeMap($container->getExtensionConfig('doctrine')[0] ?? null, 'dbal');

        self::assertSame(OrmRegistrar::types()[EmailAddressType::NAME], $orm[EmailAddressType::NAME]);
        self::assertSame(FirstClashingRegistrar::class, $orm['fixture_shared']);
        self::assertArrayHasKey('fixture_first', $orm);
        self::assertArrayHasKey('fixture_second', $orm);
        self::assertCount(\count(OrmRegistrar::types()) + 3, $orm);
        self::assertNotSame(SecondClashingRegistrar::class, $orm['fixture_shared']);
    }

    public function testAParameterAsDomainTypesFlagFails(): void
    {
        $bundle = new Msstc4phpVoBaseBundle();
        $container = $this->containerWith(['doctrine']);
        $this->loadBlocks($bundle, $container, [['domain_types' => '%domain_types%']]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('msstc4php_vo_base.domain_types must be a literal boolean or null.');

        $bundle->prependExtension($this->configurator($container), $container);
    }

    /**
     * Type names are identical on both sides, so the two maps can only be told
     * apart by their class values.
     */
    public function testBothExtensionsGetTheirOwnBranch(): void
    {
        $this->requireDoctrinePackage();

        $container = $this->containerWith(['doctrine', 'doctrine_mongodb']);

        new Msstc4phpVoBaseBundle()->prependExtension($this->configurator($container), $container);

        $orm = $this->typeMap($container->getExtensionConfig('doctrine')[0] ?? null, 'dbal');
        $odm = $this->typeMap($container->getExtensionConfig('doctrine_mongodb')[0] ?? null, null);

        self::assertSame(\array_keys($orm), \array_keys($odm));

        foreach ($orm as $class) {
            self::assertStringContainsString('\\Orm\\', $class);
        }

        foreach ($odm as $class) {
            self::assertStringContainsString('\\Odm\\', $class);
        }
    }

    public function testNothingHappensWhenTheDoctrineExtensionIsAbsent(): void
    {
        $this->requireDoctrinePackage();

        $container = $this->containerWith([]);

        new Msstc4phpVoBaseBundle()->prependExtension($this->configurator($container), $container);

        self::assertSame([], $container->getExtensionConfig('doctrine'));
    }

    /**
     * The scenario KTD3 exists for: the Doctrine extension is there, but the
     * Doctrine package is not installed. Silence, not an error.
     */
    public function testNothingHappensWhenTheDoctrinePackageIsAbsent(): void
    {
        $container = $this->containerWith(['doctrine', 'doctrine_mongodb']);

        new BundleWithoutDoctrinePackage()->prependExtension($this->configurator($container), $container);

        self::assertSame([], $container->getExtensionConfig('doctrine'));
        self::assertSame([], $container->getExtensionConfig('doctrine_mongodb'));
    }

    public function testExplicitFalseSuppressesRegistration(): void
    {
        $this->requireDoctrinePackage();

        $container = $this->containerWith(['doctrine']);
        $container->prependExtensionConfig('msstc4php_vo_base', ['orm' => false]);

        new Msstc4phpVoBaseBundle()->prependExtension($this->configurator($container), $container);

        self::assertSame([], $container->getExtensionConfig('doctrine'));
    }

    public function testExplicitTrueFailsLoudlyWhenThePackageIsMissing(): void
    {
        $container = $this->containerWith(['doctrine']);
        $container->prependExtensionConfig('msstc4php_vo_base', ['orm' => true]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('#msstc4php/vo-base-doctrine or the Doctrine stack it needs is not installed#');

        new BundleWithoutDoctrinePackage()->prependExtension($this->configurator($container), $container);
    }

    public function testExplicitTrueFailsLoudlyWhenTheExtensionIsMissing(): void
    {
        $this->requireDoctrinePackage();

        $container = $this->containerWith([]);
        $container->prependExtensionConfig('msstc4php_vo_base', ['orm' => true]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/"doctrine" extension is not registered/');

        new Msstc4phpVoBaseBundle()->prependExtension($this->configurator($container), $container);
    }

    public function testPrependingTwiceDoesNotDuplicateTypes(): void
    {
        $this->requireDoctrinePackage();

        $container = $this->containerWith(['doctrine']);
        $bundle = new Msstc4phpVoBaseBundle();

        $bundle->prependExtension($this->configurator($container), $container);
        $bundle->prependExtension($this->configurator($container), $container);

        $configs = $container->getExtensionConfig('doctrine');
        self::assertCount(2, $configs);
        self::assertSame($configs[0], $configs[1], 'Repeated passes must stay identical, not accumulate different maps.');
    }

    public function testValidatorCanBeSwitchedOff(): void
    {
        $container = new ContainerBuilder();

        new Msstc4phpVoBaseBundle()->loadExtension(['validator' => false, 'serializer' => false, 'value_resolver' => false], $this->configurator($container), $container);

        self::assertFalse($container->hasDefinition(ValidValueObjectValidator::class));
    }

    public function testALaterConfigBlockSwitchesTypesOff(): void
    {
        $this->requireDoctrinePackage();

        $bundle = new Msstc4phpVoBaseBundle();
        $container = $this->containerWith(['doctrine']);
        $this->loadBlocks($bundle, $container, [['validator' => true], ['orm' => false]]);

        $bundle->prependExtension($this->configurator($container), $container);

        self::assertSame([], $container->getExtensionConfig('doctrine'));
    }

    public function testTheLastBlockWinsForTheSameKey(): void
    {
        $bundle = new BundleWithoutDoctrinePackage();
        $container = $this->containerWith(['doctrine']);
        $this->loadBlocks($bundle, $container, [['orm' => true], ['orm' => false]]);

        $bundle->prependExtension($this->configurator($container), $container);

        self::assertSame([], $container->getExtensionConfig('doctrine'));
    }

    public function testAParameterAsFlagFailsInsteadOfMeaningAutoDetect(): void
    {
        $bundle = new Msstc4phpVoBaseBundle();
        $container = $this->containerWith(['doctrine']);
        $this->loadBlocks($bundle, $container, [['orm' => '%orm_enabled%']]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('msstc4php_vo_base.orm must be a literal boolean or null.');

        $bundle->prependExtension($this->configurator($container), $container);
    }

    public function testALaterConfigBlockDemandsTypes(): void
    {
        $bundle = new BundleWithoutDoctrinePackage();
        $container = $this->containerWith(['doctrine']);
        $this->loadBlocks($bundle, $container, [['odm' => false], ['orm' => true]]);

        $this->expectException(LogicException::class);

        $bundle->prependExtension($this->configurator($container), $container);
    }

    public function testValidatorStaysQuietWhenItsPackageIsMissing(): void
    {
        $container = new ContainerBuilder();

        new BundleWithoutValidator()->loadExtension(['serializer' => false, 'value_resolver' => false], $this->configurator($container), $container);

        self::assertFalse($container->hasDefinition(ValidValueObjectValidator::class));
    }

    public function testDemandingAMissingValidatorFails(): void
    {
        $container = new ContainerBuilder();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('symfony/validator is not installed');

        new BundleWithoutValidator()->loadExtension(['validator' => true], $this->configurator($container), $container);
    }

    public function testSerializerAndValueResolverCanBeSwitchedOff(): void
    {
        $container = new ContainerBuilder();

        new Msstc4phpVoBaseBundle()->loadExtension(['validator' => false, 'serializer' => false, 'value_resolver' => false], $this->configurator($container), $container);

        self::assertFalse($container->hasDefinition(ValueObjectNormalizer::class));
        self::assertFalse($container->hasDefinition(ValueObjectValueResolver::class));
    }

    public function testSerializerStaysQuietWhenItsPackageIsMissing(): void
    {
        $container = new ContainerBuilder();

        new BundleWithoutSerializer()->loadExtension(['validator' => false, 'value_resolver' => false], $this->configurator($container), $container);

        self::assertFalse($container->hasDefinition(ValueObjectNormalizer::class));
    }

    public function testDemandingAMissingSerializerFails(): void
    {
        $container = new ContainerBuilder();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('msstc4php_vo_base.serializer is enabled but symfony/serializer is not installed.');

        new BundleWithoutSerializer()->loadExtension(['validator' => false, 'serializer' => true], $this->configurator($container), $container);
    }

    /**
     * @param list<array<string, bool|string>> $blocks
     */
    private function loadBlocks(Msstc4phpVoBaseBundle $bundle, ContainerBuilder $container, array $blocks): void
    {
        $extension = $bundle->getContainerExtension();
        self::assertNotNull($extension);
        $container->registerExtension($extension);

        foreach ($blocks as $block) {
            $container->loadFromExtension('msstc4php_vo_base', $block);
        }
    }

    /**
     * The positive paths need the Doctrine package and the Doctrine stack behind
     * it. Skipping rather than failing keeps this suite meaningful in the CI job
     * that removes both to prove the bundle works without them -- the negative
     * paths still run there, which is exactly what that job is checking.
     */
    private function requireDoctrinePackage(): void
    {
        if (!\class_exists(OrmRegistrar::class) || !\class_exists(Type::class)) {
            self::markTestSkipped('msstc4php/vo-base-doctrine and its Doctrine stack are not installed.');
        }
    }

    private function requireDomainPackages(): void
    {
        $this->requireDoctrinePackage();

        foreach ([...self::DOMAIN_ORM_REGISTRARS, ...self::DOMAIN_ODM_REGISTRARS] as $registrar) {
            if (!\class_exists($registrar)) {
                self::markTestSkipped(\sprintf('%s is not installed.', $registrar));
            }
        }
    }

    /**
     * The base map plus every domain registrar that is installed, in the order the bundle merges them.
     *
     * @param array<string, class-string> $base
     * @param list<class-string> $registrars
     *
     * @return array<string, class-string>
     */
    private function installedTypes(array $base, array $registrars): array
    {
        foreach ($registrars as $registrar) {
            if (\class_exists($registrar)) {
                /** @var array<string, class-string> $types */
                $types = $registrar::types();
                $base += $types;
            }
        }

        return $base;
    }

    /**
     * @param list<string> $extensionAliases
     */
    private function containerWith(array $extensionAliases): ContainerBuilder
    {
        $container = new ContainerBuilder();

        foreach ($extensionAliases as $alias) {
            $container->registerExtension(new StubExtension($alias));
        }

        return $container;
    }

    /**
     * Narrows one prepended configuration entry down to its name => class map.
     *
     * @return array<string, string>
     */
    private function typeMap(mixed $config, ?string $section): array
    {
        self::assertIsArray($config);

        if ($section !== null) {
            $config = $config[$section] ?? null;
            self::assertIsArray($config);
        }

        $types = $config['types'] ?? null;
        self::assertIsArray($types);

        $map = [];

        foreach ($types as $name => $class) {
            self::assertIsString($name);
            self::assertIsString($class);
            $map[$name] = $class;
        }

        return $map;
    }

    private function configurator(ContainerBuilder $container): ContainerConfigurator
    {
        return TestConfigurator::for($container);
    }
}
