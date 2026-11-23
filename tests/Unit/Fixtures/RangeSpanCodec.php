<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit\Fixtures;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Payload\PayloadCodec;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use Override;

/**
 * @implements PayloadCodec<IntRange>
 */
final readonly class RangeSpanCodec implements PayloadCodec
{
    #[Override]
    public function valueObjectClass(): string
    {
        return IntRange::class;
    }

    /**
     * @return array{span: string}
     */
    #[Override]
    public function encode(ValueObject $value): array
    {
        if (!$value instanceof IntRange) {
            throw new LogicException('An IntRange is expected.');
        }

        return ['span' => $value->min . '..' . $value->max];
    }

    #[Override]
    public function decode(array $payload): IntRange
    {
        $text = $payload['span'] ?? null;
        if (!\is_string($text)) {
            throw new LogicException('A range text is expected.');
        }

        [$min, $max] = \explode('..', $text . '..');

        return new IntRange((int) $min, (int) $max);
    }
}
