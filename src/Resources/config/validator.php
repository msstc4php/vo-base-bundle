<?php

declare(strict_types=1);

use Msstc4Symfony\VoBaseBridge\Framework\Validator\ValidValueObjectValidator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(ValidValueObjectValidator::class)
        ->private()
        ->tag('validator.constraint_validator')
    ;
};
