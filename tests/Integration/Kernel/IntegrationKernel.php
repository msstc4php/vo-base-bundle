<?php

declare(strict_types=1);

namespace Msstc4Symfony\VoBaseBridge\Test\Integration\Kernel;

use Msstc4Symfony\VoBaseBridge\ValueObjectBridgeBundle;
use Override;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Micro-kernel for the integration tests: FrameworkBundle, the bundle under
 * test, and whichever Doctrine bundle the case needs.
 *
 * @phpstan-type KernelConfig array<string, array<string, mixed>>
 */
final class IntegrationKernel extends Kernel
{
    /**
     * @param list<BundleInterface> $extraBundles
     * @param KernelConfig $extraConfig
     */
    public function __construct(
        private readonly array $extraBundles = [],
        private readonly array $extraConfig = [],
        private readonly string $id = 'default',
    ) {
        parent::__construct('test', true);
    }

    /**
     * @return iterable<BundleInterface>
     */
    #[Override]
    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield from $this->extraBundles;
        yield new ValueObjectBridgeBundle();
    }

    #[Override]
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'secret' => 'integration',
                'test' => true,
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'php_errors' => ['log' => true],
                'validation' => ['enabled' => true],
            ]);

            foreach ($this->extraConfig as $extension => $config) {
                $container->loadFromExtension($extension, $config);
            }
        });
    }

    #[Override]
    public function getCacheDir(): string
    {
        return \sys_get_temp_dir() . '/vo-bridge-kernel/' . $this->id . '/cache';
    }

    #[Override]
    public function getLogDir(): string
    {
        return \sys_get_temp_dir() . '/vo-bridge-kernel/' . $this->id . '/log';
    }
}
