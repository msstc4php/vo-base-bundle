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
- Input goes through the mapper's `fromUntrustedPayload()`. `supportsDenormalization()` claims every supported value
  object **including** trusted-source-only ones (`BoundedString`) and `denormalize()` refuses those: declining them
  is not safe, because `ObjectNormalizer` (priority -1000) would then build a `BoundedString` through its public
  constructor from the client's `{value, minLength, maxLength}`. Output declines them, so `BoundedString` keeps the
  `JsonSerializableNormalizer` form (bare string) and publishes no limits.
- Expected types in `NotNormalizableValueException` are the mapper's `payloadType()` (string/int/float/array), never
  the class: `RequestPayloadValueResolver` always renders "This value should be of type {{ type }}." from them and
  puts the exception message into a `hint` parameter (only when `useMessageForUser`). `useMessageForUser` is false
  for `InvalidPayloadException` (its message names classes), true for invalid content.
- `ValueObjectValueResolver`'s 404 message contains the class and the value object's message, as Symfony's
  `BackedEnumValueResolver` does. Production renders only the status text; apps that render exception messages show
  it.
- `ValueObjectValueResolver` reads route attributes only, like `BackedEnumValueResolver`, and leaves enums,
  composites, variadics and untyped arguments to others. Invalid → `NotFoundHttpException`.

## No class names in client-facing errors

Every path that reaches an API client avoids internal class names: the normalizer reports JSON types,
`ValidValueObject`'s `{{ type }}` is a label derived from the short class name ("email address"), the Laravel rule's
`:type` uses the same rule. Only the route resolver's 404 message names the class, and Symfony shows that text in debug
mode only.
