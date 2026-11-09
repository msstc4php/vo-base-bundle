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
        "msstc4php/vo-base-bundle": "^0.4"
    }
}
```

Doctrine is **not** required. If you want the field types, also add
`{ "type": "vcs", "url": "https://github.com/msstc4php/vo-base-doctrine" }` to `repositories` and
require `msstc4php/vo-base-doctrine:^0.5`; the bundle picks it up on its own.

## Enable the bundle

```php
// config/bundles.php
return [
    MSSTC4PHP\ValueObject\Bundle\Msstc4phpVoBaseBundle::class => ['all' => true],
];
```

That is the whole setup. Each of the three groups registers when its dependency
is installed and its extension is present, and stays quiet otherwise.

## Configuration

```yaml
# config/packages/msstc4php_vo_base.yaml
msstc4php_vo_base:
    orm: ~        # null auto-detects, false suppresses, true demands
    odm: ~
    validator: ~
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
