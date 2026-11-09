<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Tests\Support;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class TestConfigurator
{
    public static function for(ContainerBuilder $container): ContainerConfigurator
    {
        $instanceof = [];

        return new ContainerConfigurator(
            $container,
            new PhpFileLoader($container, new FileLocator()),
            $instanceof,
            'dummy',
            'dummy',
        );
    }
}
