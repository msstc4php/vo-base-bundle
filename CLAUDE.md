# CLAUDE.md

Guidance for Claude Code in this repository. Deep references live under `.claude/docs/`.

## What this is

Symfony bundle (`msstc4php/vo-base-bundle`, namespace `MSSTC4PHP\ValueObject\Bundle`, config alias
`msstc4php_vo_base`): Validator constraints for `msstc4php/vo-base`, plus Doctrine type registration when
`msstc4php/vo-base-doctrine` is installed. PHP >= 8.4 (`php84` profile), Symfony 7.4 / 8.x.

## Common commands

Local install against the sibling `../vo-base` and `../vo-base-doctrine` checkouts: `COMPOSER=composer-local.json composer update
--ignore-platform-req=php`. In CI the install is `composer-ci.json`.

- `COMPOSER=composer-local.json make check`, `make test`, `make infection`, `make fix`.
- `php ~/PhpstormProjects/msstc4symfony/bundle-standard/bin/verify-standard.php .` — standard compliance (the same
  check CI runs).

## Pointers

- `.claude/docs/architecture.md` — layers and the Doctrine guard.
- `.claude/docs/known-issues.md` — read before chasing a "weird" failure.
