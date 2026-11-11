<?php

declare(strict_types=1);

use MSSTC4PHP\ValueObject\Bundle\Framework\Serializer\ValueObjectNormalizer;
use MSSTC4PHP\ValueObject\Payload\ValueObjectMapper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set(ValueObjectNormalizer::class)
        ->private()->arg('$mapper', service(ValueObjectMapper::class))
        ->tag('serializer.normalizer')
    ;
};
