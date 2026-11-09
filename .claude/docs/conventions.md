# Conventions

## Package family naming (since 2026-11-09 UTC)

The family follows the convention of third-party libraries with framework add-ons (`ramsey/uuid` →
`ramsey/uuid-doctrine`): one vendor, the core name plus the integration as a suffix, `-bundle` for the package that
ships a Symfony bundle class.

| Package | Namespace root |
|---|---|
| `msstc4php/vo-base` | `MSSTC4PHP\ValueObject\` |
| `msstc4php/vo-base-doctrine` | `MSSTC4PHP\ValueObject\Doctrine\` |
| `msstc4php/vo-base-bundle` | `MSSTC4PHP\ValueObject\Bundle\` |

The shared prefix is safe because the segment after `ValueObject\` names the package: an FQCN still says which
package brought the class. The core must never get a `src/Doctrine/` or `src/Bundle/` directory.

The bundle class follows Symfony's `<Vendor><Name>Bundle` rule, so the config alias derived from it is
`msstc4php_vo_base`. Tests live under `<root>Tests\` in all three packages.

## Doctrine in require-dev only

The Doctrine stack and `msstc4php/vo-base-doctrine` are dev dependencies, present solely
for the bundle and integration tests. The CI isolation job removes all of them
and still expects everything to load — that job is what keeps the "suggest, not
require" claim honest.
