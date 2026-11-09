# Known issues

## `class_exists` on the registrar is not enough

The registrar's type map names classes extending the Doctrine base type, and
reading the map autoloads them. A consumer who installed the Doctrine bridge
for ODM only has the registrar but no DBAL, so the ORM path would fatal. Both
halves are checked.

## `extensionAlias` is empty until `getContainerExtension()` runs

`AbstractBundle` fills it lazily. Reading `$this->extensionAlias` directly in
`prependExtension()` finds an empty string, `getExtensionConfig('')` returns
nothing, and every configuration key is silently ignored — including explicit
`false` and explicit `true`. Resolve it through `getContainerExtension()`.

A subclass inherits the derivation from its own class name, so a test double
must pin `$extensionAlias` or it will look for configuration under its own name.

## The bundle is not final

A bundle is a framework extension point, and overriding the two registrar
lookups is how the "package not installed" path is exercised without
uninstalling it.

## DBAL registers types on connection, ODM at boot

DoctrineBundle hands the prepended map to the connection factory, so
`Type::hasType()` is false right after boot and true once a connection is
built. The ODM registry fills at boot. Integration tests have to account for
the difference.

## The ORM integration kernel configures `dbal` only

Enabling the `orm` section pulls in the proxy factory, which then wants
`symfony/var-exporter` or native lazy objects. Type registration goes through
`doctrine.dbal.types` and needs none of it.

## Nested test directories claim files twice

`tests/Bundle` contains `tests/Bundle/Integration`, so without an explicit
`<exclude>` PHPUnit assigns those files to both suites and warns.

## Inherited Rector warnings

Three skip entries copied from the core are deprecated or never registered.
Noise, not failures; kept so the repositories' configs stay line-comparable.

## Relocated from `msstc4php` on 2026-10-31 UTC

Superseded by the 2026-11-09 UTC section below; kept as history.

The package moved from `msstc4php/vo-base-symfony-bridge` to
`../msstc4symfony/vo-base-bridge-symfony` and became `msstc4symfony/vo-base-bridge`,
namespace `Msstc4Symfony\VoBaseBridge`. The name follows
`dto-generator-bridge-symfony` (package `msstc4symfony/dto-generator-bridge`).

The move did **not** bring the package to `bundle-standard`:
`php ../bundle-standard/bin/verify-standard.php .` still reports violations,
among them the non-canonical `src/Bundle` and `src/Validator` layout and the
missing standard `checks.yml`. The current `ci.yml` clones a core branch that is
not on the remote yet, so it cannot pass until the core is pushed.

## Back under `msstc4php` as `vo-base-bundle` on 2026-11-09 UTC

The family was renamed to the third-party-library convention (see `conventions.md`): this package became
`msstc4php/vo-base-bundle` (namespace `MSSTC4PHP\ValueObject\Bundle`, class `Msstc4phpVoBaseBundle`, alias
`msstc4php_vo_base`), the Doctrine package `msstc4php/vo-base-doctrine`. The checkout sits next to both siblings
in `msstc4php/`, so the path repositories are `../vo-base` and `../vo-base-doctrine`.

- `vendor/msstc4php/*` are relative symlinks: after a move, delete them and run
  `COMPOSER=composer-local.json composer update msstc4php/vo-base msstc4php/vo-base-doctrine`.
- `bundle-standard` before v4.1.0 accepts only the `msstc4symfony` vendor (`ComposerManifestRule::VENDOR`, since 4.1.0 `VENDORS`), so
  `checks.yml` must pin `php-bundle.yml@v4.1.0` or later.

## Standard layout since 2026-11-02 UTC

The bundle class sits at `src/<Name>Bundle.php`, constraints in `src/Framework/Validator/`, and the
validator service in `src/Resources/config/validator.php`, because the `BundleRoot` layer may not reference `Framework`.
That file executes under coverage and cannot be listed in `#[CoversClass]`, so its test is in `tests/Integration`.

- `/tmp/vo-base-bundle-kernel` is shared by every install of this package: after switching dependencies delete it, or a
  stale container gives odd `TypeError`s.
- `doctrine/doctrine-bundle ^2.18` and `symfony/doctrine-bridge` in `composer-ci.json` are real floors: lower ones
  fatal under `--prefer-lowest`.
- CI resolves the siblings from tagged GitHub releases (`minimum-stability: stable`), so a change across packages is
  released core first, then `msstc4php/vo-base-doctrine`, then this package. Tags made before the 2026-11-09 UTC
  rename carry the old package names, so `msstc4php/vo-base-doctrine` resolves only from v0.5.0 on.

## The validator import line has no mutation coverage

`$configurator->import(...)` in `loadExtension` is exercised only by `tests/Integration`, whose classes are
`#[CoversNothing]`: the imported file cannot be listed in coverage metadata, and the shared `phpunit.xml.dist` cannot
change. Infection counts removing that line as uncovered. Accepted; `ValidatorRegistrationTest` still catches it.

## Integration kernels share a container cache across checkouts

`IntegrationKernel` caches under `sys_get_temp_dir()/vo-base-bundle-kernel/<id>`, so a second checkout or a
`--prefer-lowest` copy reuses a container compiled against other vendor versions and fails with
signature errors (e.g. `ConnectionFactory::createConnection(): Argument #3 ... array given`). Delete
that directory when switching dependency sets locally; CI runners start clean.
