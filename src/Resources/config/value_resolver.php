<?php

declare(strict_types=1);

use MSSTC4PHP\ValueObject\Bundle\Framework\HttpKernel\ValueObjectValueResolver;
use MSSTC4PHP\ValueObject\Payload\ValueObjectMapper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ValueObjectMapper::class)->private();

    $services
        ->set(ValueObjectValueResolver::class)
        ->private()->arg('$mapper', service(ValueObjectMapper::class))
        ->tag('controller.argument_value_resolver', ['priority' => ValueObjectValueResolver::PRIORITY, 'name' => ValueObjectValueResolver::class])
    ;
};
