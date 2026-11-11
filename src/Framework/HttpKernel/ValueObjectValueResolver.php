<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Framework\HttpKernel;

use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Payload\ValueObjectMapper;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A route attribute becomes the value object the controller argument is typed with: `/users/{email}` into
 * `EmailAddress $email`. Mirrors Symfony's BackedEnumValueResolver: route attributes only, an invalid value is a
 * 404 (the route does not exist for it), the ISO enums are left to that resolver.
 *
 * Only scalar-backed value objects: a route attribute is a string, so composites cannot arrive this way. It must
 * run before RequestAttributeValueResolver (priority 100), which would hand the raw string to the typed argument.
 */
final readonly class ValueObjectValueResolver implements ValueResolverInterface
{
    public const int PRIORITY = 105;

    public function __construct(
        private ValueObjectMapper $mapper,
    ) {
    }

    /**
     * @return list<object|null>
     */
    #[Override]
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $class = $argument->getType();
        $name = $argument->getName();

        if ($class === null || $argument->isVariadic() || !$this->mapper->isScalarBacked($class) || !$request->attributes->has($name)) {
            return [];
        }

        $raw = $request->attributes->get($name);
        if ($raw === null || $raw instanceof $class) {
            return [$raw];
        }

        try {
            return [$this->mapper->fromUntrustedPayload($class, $raw)];
        } catch (InvalidValueObjectException $exception) {
            throw new NotFoundHttpException(\sprintf('Could not resolve the "%s $%s" controller argument: %s', $class, $name, $exception->getMessage()), $exception);
        }
    }
}
