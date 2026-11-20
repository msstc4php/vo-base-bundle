<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Framework\Validator;

use Attribute;
use MSSTC4PHP\ValueObject\Finance\CreditCardNumber;

/**
 * Asserts that a string is a valid {@see CreditCardNumber}. The message does not quote the value: a rejected card
 * number is often a real PAN with one typo.
 *
 * @deprecated following the deprecation of {@see CreditCardNumber} in vo-base 1.3, use
 *             `new ValidValueObject(PaymentCardNumber::class, 'The value is not a valid payment card number.')` with
 *             MSSTC4PHP\ValueObject\Financial\Card\PaymentCardNumber from msstc4php/vo-finance, a message without
 *             `{{ value }}`; removed no earlier than 2.0.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class AssertCreditCardNumber extends ValidValueObject
{
    /**
     * @param array<string>|null $groups
     */
    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(CreditCardNumber::class, 'The value is not a valid credit card number.', $groups, $payload);
    }
}
