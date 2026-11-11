# Integrations

## The bundle

One line in `bundles.php`. Extension alias: `msstc4php_vo_base`.

```yaml
msstc4php_vo_base:
    orm: ~        # null auto-detects; false suppresses; true demands
    odm: ~
    validator: ~
    serializer: ~
    value_resolver: ~  # its dependency (symfony/http-kernel) is required, so null means on
```

Each group is independent:

| Value | Behaviour |
|---|---|
| `null` (default) | registers when both the dependency and the extension are present, stays quiet otherwise |
| `false` | never registers, even when everything is available |
| `true` | registers, or throws naming what is missing — the package or the extension |

`prependExtension()` writes into `doctrine.dbal.types` and `doctrine_mongodb.types`.
`loadExtension()` imports one `Resources/config/<group>.php` per enabled group: `ValidValueObjectValidator`
(`validator.constraint_validator`), `ValueObjectNormalizer` (`serializer.normalizer`, default priority, which is
ahead of `JsonSerializableNormalizer`), `ValueObjectValueResolver` (`controller.argument_value_resolver`, priority
105). Both of the latter share one private `Payload\ValueObjectMapper` service (`payload.php`, imported when either
group is on), which receives every service tagged `msstc4php_vo_base.payload_codec`
(`Msstc4phpVoBaseBundle::PAYLOAD_CODEC_TAG`); any `PayloadCodec` service is autoconfigured with it, so an
application's own composite value objects work in the Serializer without wiring.

The bundle never calls `TypeRegistrar::registerAll()`. Static registration
remains the route for bare Doctrine; mixing both in one process is unnecessary
and the `hasType` guard would survive it anyway.

## Symfony Validator

`ValidValueObject` plus 14 `Assert*` constraints, each naming one value object.
Validation runs the value object's own constructor and turns its exception into
a violation, so the rules live in one place rather than being restated as
constraint options.

## Serializer and route arguments

- `ValueObjectNormalizer` uses the core mapper both ways; `getSupportedTypes()` returns `[ValueObject::class => true]`,
  which the Serializer matches by subclass. Errors become `NotNormalizableValueException` with
  `useMessageForUser: true`, so `#[MapRequestPayload]` answers 422 with the value object's message and the
  `deserialization_path`.
- Input goes through the mapper's `fromUntrustedPayload()`; `supportsUntrusted()` decides support. A codec marked
  `TrustedSourceOnly` (core: `BoundedStringCodec`) is therefore excluded in both directions: its payload carries
  `minLength`/`maxLength`, so reading it from a request is a validation bypass and writing it publishes internal
  limits. `BoundedString` falls through to `JsonSerializableNormalizer` (bare string) on output and to no
  denormalizer on input; a direct `denormalize()` call throws.
- A wrong type or shape (`InvalidPayloadException`) is reported with `useMessageForUser: false`: its message names
  internal classes. Invalid content keeps the value object's message for the client.
- `ValueObjectValueResolver` reads route attributes only, like `BackedEnumValueResolver`, and leaves enums,
  composites, variadics and untyped arguments to others. Invalid → `NotFoundHttpException`.
