<?php

declare(strict_types=1);

use MSSTC4PHP\ValueObject\Bundle\Framework\Payload\MapperFactory;
use MSSTC4PHP\ValueObject\Bundle\Msstc4phpVoBaseBundle;
use MSSTC4PHP\ValueObject\Payload\ValueObjectMapper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(ValueObjectMapper::class)
        ->private()
        ->factory(MapperFactory::create(...))
        ->args([tagged_iterator(Msstc4phpVoBaseBundle::PAYLOAD_CODEC_TAG), param('msstc4php_vo_base.codec_providers')])
    ;
};
