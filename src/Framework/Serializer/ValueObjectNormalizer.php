<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Framework\Serializer;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidPayloadException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Payload\ValueObjectMapper;
use Override;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Value objects to and from their storage form, so a DTO behind #[MapRequestPayload] can type a property as
 * EmailAddress. Invalid input becomes a NotNormalizableValueException carrying the value object's message, which
 * the serializer turns into a 422 with the property path. The ISO enums are left to Symfony's own enum normalizer.
 *
 * Classes the mapper marks trusted-source only (BoundedString) are left out in both directions: their payload carries
 * their own limits, so accepting it from a request would let the client choose them, and emitting it would publish
 * them. BoundedString keeps the JsonSerializable output (the bare string).
 */
final readonly class ValueObjectNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(
        private ValueObjectMapper $mapper,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return string|int|float|array<string, scalar|null>
     */
    #[Override]
    public function normalize(mixed $data, ?string $format = null, array $context = []): string|int|float|array
    {
        if (!$data instanceof ValueObject) {
            throw new InvalidArgumentException(\sprintf('Expected a value object, %s given.', get_debug_type($data)));
        }

        return $this->mapper->toPayload($data);
    }

    /**
     * @param array<string, mixed> $context
     */
    #[Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ValueObject && $this->handles($data::class);
    }

    /**
     * @param array<string, mixed> $context
     */
    #[Override]
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!is_subclass_of($type, ValueObject::class) || !$this->mapper->supportsUntrusted($type)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a value object this normalizer reads from input.', $type));
        }

        try {
            return $this->mapper->fromUntrustedPayload($type, $data);
        } catch (InvalidValueObjectException $exception) {
            $path = $context['deserialization_path'] ?? null;

            // A wrong type or shape names internal classes; Symfony's generic "should be of type" text is enough there.
            throw NotNormalizableValueException::createForUnexpectedDataType($exception->getMessage(), $data, [$type], \is_string($path) ? $path : null, !$exception instanceof InvalidPayloadException, 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    #[Override]
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $this->handles($type);
    }

    /**
     * Support depends on the class alone, so the serializer may cache the answer per type.
     *
     * @return array<class-string, bool>
     */
    #[Override]
    public function getSupportedTypes(?string $format): array
    {
        return [ValueObject::class => true];
    }

    /**
     * Value objects only (the ISO enums are Symfony's), and none whose payload carries its own constraints.
     */
    private function handles(string $class): bool
    {
        return is_subclass_of($class, ValueObject::class) && $this->mapper->supportsUntrusted($class);
    }
}
