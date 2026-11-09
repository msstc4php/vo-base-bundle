<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Tests\Unit\Fixtures;

use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class StubExtension extends Extension
{
    public function __construct(private readonly string $alias)
    {
    }

    #[Override]
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @param array<array-key, mixed> $configs
     */
    #[Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
    }
}
