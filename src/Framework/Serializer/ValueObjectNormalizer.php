<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Framework\Serializer;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Payload\ValueObjectMapper;
use MSSTC4PHP\ValueObject\Primitive\BoundedString;
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
 * BoundedString is left out in both directions: its payload carries the length limits, so accepting it from a
 * request would let the client choose them, and emitting it would publish them. It keeps the JsonSerializable
 * output (the bare string).
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
        if (!is_subclass_of($type, ValueObject::class)) {
            throw new InvalidArgumentException(\sprintf('Expected a value object class, "%s" given.', $type));
        }

        try {
            return $this->mapper->fromPayload($type, $data);
        } catch (InvalidValueObjectException $exception) {
            $path = $context['deserialization_path'] ?? null;

            throw NotNormalizableValueException::createForUnexpectedDataType($exception->getMessage(), $data, [$type], \is_string($path) ? $path : null, true, 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    #[Override]
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_subclass_of($type, ValueObject::class) && $this->handles($type);
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

    private function handles(string $class): bool
    {
        return $class !== BoundedString::class && $this->mapper->supports($class);
    }
}
