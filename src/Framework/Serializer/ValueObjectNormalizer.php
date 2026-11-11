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
 * EmailAddress. Invalid input becomes a NotNormalizableValueException, which #[MapRequestPayload] turns into a 422
 * with the property path. The ISO enums are left to Symfony's own enum normalizer.
 *
 * The expected type it reports is the JSON type (string, int, float, array), never the class: Symfony builds the
 * violation text from it ("This value should be of type string."), so a class name would reach API clients. The
 * value object's own message travels as the violation's "hint" parameter.
 *
 * Classes the mapper marks trusted-source only (BoundedString) are claimed on input and always refused: declining
 * them would hand them to ObjectNormalizer, which would build one through its constructor with limits the client
 * chose. On output they are declined, keeping the JsonSerializable form (the bare string) rather than publishing
 * the limits.
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
        return $data instanceof ValueObject && $this->mapper->supportsUntrusted($data::class);
    }

    /**
     * @param array<string, mixed> $context
     */
    #[Override]
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!is_subclass_of($type, ValueObject::class) || !$this->mapper->supports($type)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a value object this normalizer reads.', $type));
        }

        try {
            return $this->mapper->fromUntrustedPayload($type, $data);
        } catch (InvalidValueObjectException $exception) {
            $path = $context['deserialization_path'] ?? null;

            throw NotNormalizableValueException::createForUnexpectedDataType($exception->getMessage(), $data, [$this->mapper->payloadType($type)], \is_string($path) ? $path : null, /* A wrong type or shape names internal classes in its message; invalid content does not. */ !$exception instanceof InvalidPayloadException, 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    #[Override]
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_subclass_of($type, ValueObject::class) && $this->mapper->supports($type);
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
}
