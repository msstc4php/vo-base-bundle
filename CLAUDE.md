# CLAUDE.md

Guidance for Claude Code in this repository. Deep references live under `.claude/docs/`.

## What this is

Symfony bundle (`msstc4symfony/vo-base-bridge`, namespace `Msstc4Symfony\VoBaseBridge`, config alias
`value_object_bridge`): Validator constraints for `msstc4php/vo-base`, plus Doctrine type registration when
`msstc4php/vo-base-doctrine-bridge` is installed. PHP >= 8.4 (`php84` profile), Symfony 7.4 / 8.x.

## Common commands

Local install against the sibling `../../msstc4php` checkouts: `COMPOSER=composer-local.json composer update
--ignore-platform-req=php`. In CI the install is `composer-ci.json`.

- `COMPOSER=composer-local.json make check`, `make test`, `make infection`, `make fix`.
- `php ../bundle-standard/bin/verify-standard.php .` — standard compliance (the same check CI runs).

## Pointers

- `.claude/docs/architecture.md` — layers and the Doctrine guard.
- `.claude/docs/known-issues.md` — read before chasing a "weird" failure.
