<?php

declare(strict_types=1);

use MSSTC4PHP\ValueObject\Bundle\Framework\Validator\ValidValueObjectValidator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(ValidValueObjectValidator::class)
        ->private()
        ->tag('validator.constraint_validator')
    ;
};
