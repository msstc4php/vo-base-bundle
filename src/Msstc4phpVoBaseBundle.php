<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle;

use Doctrine\DBAL\Types\Type;
use LogicException;
use MSSTC4PHP\ValueObject\Doctrine\Orm\TypeRegistrar;
use Override;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Registers the value-object Doctrine types, the constraint validator, the
 * Serializer normalizer and the controller argument resolver.
 *
 * One line in bundles.php is the whole setup. Each of the groups is
 * independent: a group registers when its dependency is installed and its
 * extension is present, and stays quiet otherwise.
 *
 * The Doctrine types live in a separate package this one only suggests, so the
 * gate is doubled: the registrar class has to exist (package installed) and the
 * Doctrine extension has to be there (somewhere to prepend into). Requiring the
 * package instead would hand every Doctrine type to a Symfony consumer with no Doctrine
 * at all, and tie the two release cycles together.
 *
 * Not final: a bundle is a framework extension point, and overriding the two
 * registrar lookups is how the "package not installed" path gets exercised
 * without uninstalling it.
 */
class Msstc4phpVoBaseBundle extends AbstractBundle
{
    private const string ORM_REGISTRAR = TypeRegistrar::class;

    private const string ODM_REGISTRAR = \MSSTC4PHP\ValueObject\Doctrine\Odm\TypeRegistrar::class;

    // The registrar existing is not enough. Its type map names classes that
    // extend the Doctrine base type, and reading the map autoloads them, so a
    // consumer who installed the Doctrine package for ODM only would fatal on the
    // ORM path. Both halves are therefore checked.
    private const string ORM_BASE = Type::class;

    private const string ODM_BASE = \Doctrine\ODM\MongoDB\Types\Type::class;

    #[Override]
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->booleanNode('orm')->defaultNull()
                    ->info('Register the DBAL types. Null auto-detects; true fails if msstc4php/vo-base-doctrine or the doctrine extension is missing.')
                ->end()
                ->booleanNode('odm')->defaultNull()
                    ->info('Register the ODM types. Null auto-detects; true fails if msstc4php/vo-base-doctrine or the doctrine_mongodb extension is missing.')
                ->end()
                ->booleanNode('validator')->defaultNull()
                    ->info('Register the ValidValueObject constraint validator. Null auto-detects.')
                ->end()
                ->booleanNode('serializer')->defaultNull()
                    ->info('Register the Serializer normalizer for value objects. Null auto-detects symfony/serializer.')
                ->end()
                ->booleanNode('value_resolver')->defaultNull()
                    ->info('Resolve controller arguments typed with a value object from route attributes. Null means on.')
                ->end()
            ->end()
        ;
    }

    #[Override]
    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        // extensionAlias is populated lazily, inside getContainerExtension(); read
        // before that it is still the empty string and no configuration is found.
        $alias = $this->getContainerExtension()?->getAlias() ?? '';

        // Every block counts: when@env and config/packages/<env>/ arrive as separate ones, the later winning.
        $config = \array_merge(...$container->getExtensionConfig($alias));

        $this->prependTypes(
            $container,
            'doctrine',
            $this->ormTypes(),
            $this->requestedFlag($config, 'orm'),
            'orm',
            static fn (array $types): array => ['dbal' => ['types' => $types]],
        );

        $this->prependTypes(
            $container,
            'doctrine_mongodb',
            $this->odmTypes(),
            $this->requestedFlag($config, 'odm'),
            'odm',
            static fn (array $types): array => ['types' => $types],
        );
    }

    /**
     * The parent declares an untyped array, so the shape cannot be narrowed in
     * the signature without breaking contravariance; it is narrowed by check.
     *
     * @param array<array-key, mixed> $config
     */
    #[Override]
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $this->loadGroup($configurator, $this->requestedFlag($config, 'validator'), 'validator', $this->validatorAvailable(), 'symfony/validator');
        $this->loadGroup($configurator, $this->requestedFlag($config, 'serializer'), 'serializer', $this->serializerAvailable(), 'symfony/serializer');
        $this->loadGroup($configurator, $this->requestedFlag($config, 'value_resolver'), 'value_resolver', true, 'symfony/http-kernel');
    }

    protected function validatorAvailable(): bool
    {
        return \class_exists(Constraint::class);
    }

    protected function serializerAvailable(): bool
    {
        return \interface_exists(DenormalizerInterface::class);
    }

    /**
     * @return array<string, class-string>|null null when msstc4php/vo-base-doctrine is absent
     */
    protected function ormTypes(): ?array
    {
        return $this->typesFrom(self::ORM_REGISTRAR, self::ORM_BASE);
    }

    /**
     * @return array<string, class-string>|null null when msstc4php/vo-base-doctrine is absent
     */
    protected function odmTypes(): ?array
    {
        return $this->typesFrom(self::ODM_REGISTRAR, self::ODM_BASE);
    }

    /**
     * Each group is independent: off when false, or when null and its package is missing; loud when demanded but
     * missing.
     */
    private function loadGroup(ContainerConfigurator $configurator, ?bool $requested, string $group, bool $available, string $package): void
    {
        if ($requested === false || ($requested === null && !$available)) {
            return;
        }

        if (!$available) {
            throw new LogicException(\sprintf('msstc4php_vo_base.%s is enabled but %s is not installed.', $group, $package));
        }

        $configurator->import(__DIR__ . '/Resources/config/' . $group . '.php');
    }

    /**
     * @param array<string, class-string>|null $types
     * @param callable(array<string, class-string>): array<string, mixed> $shape
     */
    private function prependTypes(
        ContainerBuilder $container,
        string $extension,
        ?array $types,
        ?bool $requested,
        string $node,
        callable $shape,
    ): void {
        if ($requested === false) {
            return;
        }

        $available = $types !== null && $container->hasExtension($extension);

        if (!$available) {
            if ($requested === true) {
                $reason = $types === null
                    ? 'msstc4php/vo-base-doctrine or the Doctrine stack it needs is not installed'
                    : \sprintf('the "%s" extension is not registered', $extension);

                throw new LogicException(\sprintf('msstc4php_vo_base.%s is enabled but %s.', $node, $reason));
            }

            return;
        }

        $container->prependExtensionConfig($extension, $shape($types));
    }

    /**
     * @return array<string, class-string>|null
     */
    private function typesFrom(string $registrar, string $base): ?array
    {
        if (!\class_exists($registrar) || !\class_exists($base)) {
            return null;
        }

        /** @var array<string, class-string> $types */
        $types = $registrar::types();

        return $types;
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function requestedFlag(array $config, string $key): ?bool
    {
        $value = $config[$key] ?? null;

        // prependExtension() sees unresolved values: a parameter here would silently mean auto-detect.
        if ($value !== null && !\is_bool($value)) {
            throw new LogicException(\sprintf('msstc4php_vo_base.%s must be a literal boolean or null.', $key));
        }

        return $value;
    }
}
