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
final readonly class RangeTextCodec implements PayloadCodec
{
    #[Override]
    public function valueObjectClass(): string
    {
        return IntRange::class;
    }

    /**
     * @return array{range: string}
     */
    #[Override]
    public function encode(ValueObject $value): array
    {
        if (!$value instanceof IntRange) {
            throw new LogicException('An IntRange is expected.');
        }

        return ['range' => $value->min . '..' . $value->max];
    }

    #[Override]
    public function decode(array $payload): IntRange
    {
        $text = $payload['range'] ?? null;
        if (!\is_string($text)) {
            throw new LogicException('A range text is expected.');
        }

        [$min, $max] = \explode('..', $text . '..');

        return new IntRange((int) $min, (int) $max);
    }
}
