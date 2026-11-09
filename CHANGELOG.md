# Changelog

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
