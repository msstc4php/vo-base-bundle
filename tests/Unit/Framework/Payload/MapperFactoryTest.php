<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit\Framework\Payload;

use MSSTC4PHP\ValueObject\Bundle\Framework\Payload\MapperFactory;
use MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures\RangeCodecs;
use MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures\RangeSpanCodec;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MapperFactory::class)]
final class MapperFactoryTest extends TestCase
{
    public function testWithoutProvidersOrServicesTheCoresCodecsAreUsed(): void
    {
        self::assertSame(['min' => 1, 'max' => 2], MapperFactory::create([], [])->toPayload(new IntRange(1, 2)));
    }

    public function testAProvidersCodecsJoinTheMapper(): void
    {
        self::assertSame(['range' => '1..2'], MapperFactory::create([], [RangeCodecs::class])->toPayload(new IntRange(1, 2)));
    }

    public function testATaggedCodecReplacesAProvidersOneForTheSameClass(): void
    {
        $mapper = MapperFactory::create([new RangeSpanCodec()], [RangeCodecs::class]);

        self::assertSame(['span' => '1..2'], $mapper->toPayload(new IntRange(1, 2)));
    }
}
