# msstc4php/vo-base-bundle

Symfony Validator constraints and a bundle for
[`msstc4php/vo-base`][core].

One line in `bundles.php` registers the constraint validator and — if
[`msstc4php/vo-base-doctrine`][doctrine] happens to be installed — the
Doctrine ORM and ODM types as well.

## Install

Neither package is on Packagist yet:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/msstc4php/vo-base" },
        { "type": "vcs", "url": "https://github.com/msstc4php/vo-base-bundle" }
    ],
    "require": {
        "msstc4php/vo-base-bundle": "^1.0"
    }
}
```

Doctrine is **not** required. If you want the field types, also add
`{ "type": "vcs", "url": "https://github.com/msstc4php/vo-base-doctrine" }` to `repositories` and
require `msstc4php/vo-base-doctrine:^1.0`; the bundle picks it up on its own.

## Enable the bundle

```php
// config/bundles.php
return [
    MSSTC4PHP\ValueObject\Bundle\Msstc4phpVoBaseBundle::class => ['all' => true],
];
```

That is the whole setup. Each group registers when its dependency is installed
(and, for Doctrine, its extension is present), and stays quiet otherwise.

## Configuration

```yaml
# config/packages/msstc4php_vo_base.yaml
msstc4php_vo_base:
    orm: ~        # null auto-detects, false suppresses, true demands
    odm: ~
    validator: ~
    serializer: ~      # value objects in the Serializer, e.g. behind #[MapRequestPayload]
    value_resolver: ~  # controller arguments from route attributes; on unless false
```

`true` makes a missing dependency an error at container build time instead of
silent absence — useful when a deployment must not start without the types.

## Validating raw input

```php
use MSSTC4PHP\ValueObject\Bundle\Framework\Validator\AssertEmailAddress;

#[AssertEmailAddress]
public string $email = '';
```

The constraint runs the value object's own constructor and turns its exception
into a violation, so the rule lives in one place rather than being restated as
constraint options. `ValidValueObject` does the same for any value object by
class name.

## Serializer

With `symfony/serializer` installed, a DTO property can be typed with a value object:

```php
final readonly class SignUp
{
    public function __construct(public EmailAddress $email, public ?Port $port = null) {}
}

#[Route('/sign-up', methods: ['POST'])]
public function signUp(#[MapRequestPayload] SignUp $input): Response
```

`{"email": "not an email"}` becomes a 422 with the property path and the value object's message. Values go out in
their storage form: strings, numbers, and `{"min": 1, "max": 5}`-style objects for `IntRange`, `GeoCoordinates` and
`DateRange` (with microseconds and offset, unlike `jsonSerialize()`). `BoundedString` is never read from input: its
payload carries the length limits, so the client would choose them; validate length with `Length` instead.

## Route arguments

```php
#[Route('/users/{email}')]
public function show(EmailAddress $email): Response
```

An invalid `{email}` is a 404, as with Symfony's enum resolver. Only string-, int- and float-backed value objects;
the ISO enums are handled by Symfony itself.

## Development

```
make check   # php -l, PHPStan level max, cs-fixer, composer validate/audit, Rector, deptrac
make test    # PHPUnit: unit, integration
make fix     # cs-fixer + Rector, applied
```

The Doctrine stack sits only in `composer-ci.json`, purely for the bundle and
integration tests. A CI job installs `composer.json` without it and still expects
every class to load — that is what keeps "suggest, not require" honest.

[core]: https://github.com/msstc4php/vo-base
[doctrine]: https://github.com/msstc4php/vo-base-doctrine
