# Changelog

## 1.1.0 - 2026-11-11

**Changed:** the `{{ type }}` parameter of `ValidValueObject` (and every `Assert*`) is a lowercase label such as
"email address" instead of the class name, so violations sent to API clients name no internal class. Custom
messages using `{{ type }}` read differently.

**Added**

- `Framework\Serializer\ValueObjectNormalizer`: value objects in the Symfony Serializer, so DTOs behind
  `#[MapRequestPayload]` can use them; invalid input is a 422 with the property path. Registered when
  `symfony/serializer` is installed (`serializer` config node). `BoundedString` is excluded in both directions.
- `Framework\HttpKernel\ValueObjectValueResolver`: controller arguments typed with a string-, int- or float-backed
  value object resolve from route attributes; an invalid value is a 404 (`value_resolver` config node, on by
  default).
- Any `PayloadCodec` service is autoconfigured (`msstc4php_vo_base.payload_codec`) and reaches the normalizer and the
  resolver, so application composites work without wiring.
- Requires `msstc4php/vo-base` `^1.1` (the shared `Payload\ValueObjectMapper`); the Doctrine types need
  `msstc4php/vo-base-doctrine` `^1.1`.

## 1.0.0 - 2026-11-11

First stable release: from here on a breaking change needs 2.0. The bundle class, the `msstc4php_vo_base` config
alias and its options are part of that promise.

- Requires `msstc4php/vo-base` `^1.0`; the Doctrine types come from `msstc4php/vo-base-doctrine` `^1.0`. No code
  changes.

## 0.4.0 - 2026-11-09

**Changed (BC):** the package is renamed to the convention of third-party libraries with framework add-ons.

- Package `msstc4symfony/vo-base-bridge` is now `msstc4php/vo-base-bundle`; the GitHub repository moved to
  `msstc4php/vo-base-bundle`.
- Namespace `Msstc4Symfony\VoBaseBridge\` is now `MSSTC4PHP\ValueObject\Bundle\`.
- Bundle class `ValueObjectBridgeBundle` is now `Msstc4phpVoBaseBundle`; update `config/bundles.php`.
- Configuration alias `value_object_bridge` is now `msstc4php_vo_base`; rename `config/packages/value_object_bridge.yaml`
  and its root key.
- Requires `msstc4php/vo-base-doctrine` ^0.5 when installed (the renamed Doctrine bridge).
- Upgrading: replace the old packages and their VCS URLs with the new ones; `conflict` refuses to install an old
  package next to a new one.

## 0.3.0 - 2026-11-07

- Requires `msstc4php/vo-base` ^0.4 and, when installed, `msstc4php/vo-base-doctrine-bridge` ^0.4. The core
  validates more strictly (trailing newlines, spaces and control characters in URLs), so constraints reject such
  input now.

## 0.2.0 - 2026-11-07

- `AssertIpRange` constraint.
- Requires `msstc4php/vo-base` ^0.3 (0.1 and 0.2 are no longer accepted); the optional
  `msstc4php/vo-base-doctrine-bridge` must be ^0.3 as well.

## 0.1.1 - 2026-11-07

- Accepts `msstc4php/vo-base` and `msstc4php/vo-base-doctrine-bridge` 0.2 as well as 0.1.

## 0.1.0 - 2026-11-07

- Symfony Validator constraints for `msstc4php/vo-base` and `ValueObjectBridgeBundle`, which also registers the
  Doctrine types when `msstc4php/vo-base-doctrine-bridge` is installed.
