<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Framework\Validator;

use Attribute;
use MSSTC4PHP\ValueObject\Finance\CreditCardNumber;

/**
 * Asserts that a string is a valid {@see CreditCardNumber}.
 *
 * @deprecated following the deprecation of {@see CreditCardNumber} in vo-base 1.3, use a constraint for
 *             MSSTC4PHP\ValueObject\Financial\Card\PaymentCardNumber from msstc4php/vo-finance; removed no earlier
 *             than 2.0. Behaviour is unchanged.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class AssertCreditCardNumber extends ValidValueObject
{
    /**
     * @param array<string>|null $groups
     */
    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(CreditCardNumber::class, 'The value {{ value }} is not a valid credit card number.', $groups, $payload);
    }
}
