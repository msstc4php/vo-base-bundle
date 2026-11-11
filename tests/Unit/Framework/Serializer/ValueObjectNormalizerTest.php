<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Test\Unit\Framework\Serializer;

use DateTimeImmutable;
use MSSTC4PHP\ValueObject\Bundle\Framework\Serializer\ValueObjectNormalizer;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use MSSTC4PHP\ValueObject\Network\Port;
use MSSTC4PHP\ValueObject\Payload\ValueObjectMapper;
use MSSTC4PHP\ValueObject\Primitive\BoundedString;
use MSSTC4PHP\ValueObject\Primitive\IntRange;
use MSSTC4PHP\ValueObject\Temporal\DateRange;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Serializer;

#[CoversClass(ValueObjectNormalizer::class)]
final class ValueObjectNormalizerTest extends TestCase
{
    private ValueObjectNormalizer $normalizer;

    private Serializer $serializer;

    #[Override]
    protected function setUp(): void
    {
        $this->normalizer = new ValueObjectNormalizer(new ValueObjectMapper());
        $this->serializer = new Serializer(
            [$this->normalizer, new BackedEnumNormalizer(), new JsonSerializableNormalizer()],
            [new JsonEncoder()],
        );
    }

    public function testScalarAndCompositeValueObjectsRoundTripThroughJson(): void
    {
        self::assertSame('"a@example.com"', $this->serializer->serialize(EmailAddress::from('a@example.com'), 'json'));
        self::assertSame('8080', $this->serializer->serialize(Port::from(8080), 'json'));
        self::assertSame('{"min":1,"max":5}', $this->serializer->serialize(new IntRange(1, 5), 'json'));

        self::assertEquals(EmailAddress::from('a@example.com'), $this->serializer->deserialize('"a@example.com"', EmailAddress::class, 'json'));
        self::assertEquals(new IntRange(1, 5), $this->serializer->deserialize('{"min":1,"max":5}', IntRange::class, 'json'));
    }

    public function testDateRangeKeepsMicrosecondsUnlikeJsonSerialize(): void
    {
        $range = new DateRange(new DateTimeImmutable('2026-01-01T10:00:00.250000+02:00'), new DateTimeImmutable('2026-01-02T00:00:00Z'));

        $json = $this->serializer->serialize($range, 'json');

        self::assertTrue($range->equals($this->serializer->deserialize($json, DateRange::class, 'json')));
    }

    public function testInvalidInputIsAUserFacingDenormalizationError(): void
    {
        try {
            $this->normalizer->denormalize('not an email', EmailAddress::class, 'json', ['deserialization_path' => 'contact.email']);
            self::fail('Expected an exception.');
        } catch (NotNormalizableValueException $exception) {
            self::assertSame('contact.email', $exception->getPath());
            self::assertTrue($exception->canUseMessageForUser());
            self::assertSame(['string'], $exception->getExpectedTypes());
            self::assertSame('string', $exception->getCurrentType());
        }
    }

    public function testAPayloadOfTheWrongTypeIsADenormalizationErrorWithoutInternalNames(): void
    {
        try {
            $this->normalizer->denormalize(['min' => 1], IntRange::class);
            self::fail('Expected an exception.');
        } catch (NotNormalizableValueException $exception) {
            self::assertFalse($exception->canUseMessageForUser());
        }
    }

    public function testBoundedStringIsClaimedOnInputAndAlwaysRefused(): void
    {
        $payload = ['value' => 'x', 'minLength' => 0, 'maxLength' => null];

        self::assertTrue($this->normalizer->supportsDenormalization($payload, BoundedString::class), 'declining would hand it to ObjectNormalizer');

        try {
            $this->normalizer->denormalize($payload, BoundedString::class, 'json', ['deserialization_path' => 'name']);
            self::fail('Expected an exception.');
        } catch (NotNormalizableValueException $exception) {
            self::assertSame(['string'], $exception->getExpectedTypes());
            self::assertSame('name', $exception->getPath());
            self::assertFalse($exception->canUseMessageForUser());
        }
    }

    public function testBoundedStringKeepsItsJsonSerializeOutput(): void
    {
        $value = new BoundedString('ab', 1, 3);

        self::assertFalse($this->normalizer->supportsNormalization($value));
        self::assertSame('"ab"', $this->serializer->serialize($value, 'json'));
    }

    public function testEnumsAndForeignClassesAreLeftAlone(): void
    {
        self::assertFalse($this->normalizer->supportsNormalization(CountryCode::DE));
        self::assertFalse($this->normalizer->supportsDenormalization('DE', CountryCode::class));
        self::assertFalse($this->normalizer->supportsNormalization(new stdClass()));
        self::assertFalse($this->normalizer->supportsDenormalization('x', stdClass::class));
        self::assertSame('"DE"', $this->serializer->serialize(CountryCode::DE, 'json'));
    }

    public function testDirectCallsWithAForeignTypeAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->normalizer->denormalize('x', stdClass::class);
    }

    public function testNormalizingAForeignValueIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->normalizer->normalize(new stdClass());
    }

    public function testSupportIsCacheablePerValueObjectType(): void
    {
        self::assertSame([ValueObject::class => true], $this->normalizer->getSupportedTypes('json'));
    }
}
