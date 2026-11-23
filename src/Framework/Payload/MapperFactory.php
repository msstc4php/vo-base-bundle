<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Framework\Payload;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Payload\PayloadCodec;
use MSSTC4PHP\ValueObject\Payload\ProvidesCodecs;
use MSSTC4PHP\ValueObject\Payload\ValueObjectMapper;

/**
 * Builds the bundle's mapper from the `codec_providers` of the configuration and the tagged codec services. The
 * services come last, so an application's own codec replaces a package's one for the same class.
 *
 * @internal
 */
final class MapperFactory
{
    /**
     * @param iterable<array-key, PayloadCodec<ValueObject>> $taggedCodecs
     * @param list<class-string<ProvidesCodecs>> $providers
     */
    public static function create(iterable $taggedCodecs, array $providers): ValueObjectMapper
    {
        $codecs = [];
        foreach ($providers as $provider) {
            foreach ($provider::all() as $codec) {
                $codecs[] = $codec;
            }
        }

        foreach ($taggedCodecs as $codec) {
            $codecs[] = $codec;
        }

        return new ValueObjectMapper($codecs);
    }
}
