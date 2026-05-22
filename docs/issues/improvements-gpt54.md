# Assessment and suggested improvements

> Created by gpt-5.4 using pi on 22 May 2026

## Scope
This document reviews the current state of the repository as both:
- the PHP definition of the APM Publication API, and
- a PHP client for consuming that API.

The main goal assumed here is:

> **Keep PHP code as the primary source of truth, while making the API contract clearer, more formal, and the client implementation more robust.**

Per the request, this review does **not** treat the fact that the transcription-related DTOs are not yet wired into the client as a defect in itself. Instead, it focuses on what should be improved so those and future publication types can be integrated cleanly.

---

## 1. High-level assessment

## What is already good
- The repository is small and easy to understand.
- The package already separates:
  - transport/client logic (`PublicationApiClient`), and
  - API/data definitions (DTO-like classes in `src/ThomasInstitut/ApmPublicationApi`).
- The response objects are explicit (`PublicationApiListResponse`, `PublicationApiGetResponse`).
- The current DTOs use typed public properties, which is a good start for using PHP itself as contract documentation.
- There is already a basic test suite using Guzzle's `MockHandler`.
- Static analysis (`phpstan`) and tests pass.

## Main weaknesses
The package currently defines the API only *implicitly* through a mixture of:
- DTO classes,
- `README.md`, and
- client parsing logic.

That works for a first version, but it leaves several gaps:
- the HTTP contract is not formally described,
- some parts of the wire format are not validated robustly,
- client-side failures and server-side API errors are conflated,
- the data model is not yet rich enough to serve as a strong, machine-readable contract,
- and the current tests cover only the happy path.

In short:
- **the repository is already a useful prototype**, but
- **it is not yet a strong, formal API contract plus production-grade client**.

---

## 2. Main findings by area

## 2.1 API contract definition is spread across multiple places

At the moment, the contract is distributed across:
- `README.md`
- DTO classes
- `PublicationApiClient` parsing branches
- conventions inherited from `ThomasInstitut\StandardApi\ApiResponse`

This creates several risks:
- documentation drift,
- uncertainty about which fields are mandatory vs optional,
- unclear wire formats for nested structures,
- unclear rules for error payloads,
- and difficulty generating formal documentation.

### Recommendation
Make the PHP DTO layer the **explicit canonical contract**, and generate human- and machine-readable documentation from it.

---

## 2.2 The client is functional but not yet robust against malformed or unexpected responses

The current client does the basic job, but it trusts the server response too much.

### Observed issues

#### a) `json_decode()` is used without `JSON_THROW_ON_ERROR`
If the server returns invalid JSON, `json_decode(..., true)` returns `null`. The code then proceeds as if it had a valid array.

This can lead to:
- notices/warnings,
- misleading error messages,
- or inconsistent behavior.

#### b) Response shape validation is partial and inconsistent
`list()` and `get()` do not validate the payload in the same way.

Examples:
- `list()` checks for `publications`, but not whether the decoded value is actually an array with the expected shape.
- `get()` checks for `publicationData.type`, but does **not** first handle the case where the server returned an API-level error payload.
- `get()` therefore does not currently preserve a server error response consistently.

#### c) Client-side failures are returned as `ErrorResponse`
Transport failures, invalid JSON, malformed payloads, and real remote API errors are all represented as `ErrorResponse`.

That is convenient, but semantically muddy:
- a **server-declared API error** is not the same as
- a **network error**, or
- a **client-side decoding/validation failure**.

#### d) HTTP status handling is incomplete
There are TODOs in `PublicationApiClient` for extracting HTTP status from Guzzle exceptions.

Right now the client loses useful information such as:
- actual response code,
- response body for failed requests,
- whether the failure was transport-related or application-related.

#### e) There is a small error message bug in `get()`
In the unsupported-type branch, the message uses `$data['type']`, but the type actually lives under `publicationData['type']`.

### Recommendation
Introduce stricter decoding and a clearer distinction between:
- **remote API error responses**, and
- **local client/runtime errors**.

---

## 2.3 The DTOs are a good start, but they are not yet strong enough to be the sole contract source

The current DTOs are readable, but they still leave important parts unspecified.

### Observed issues

#### a) DTOs encode types, but not enough field semantics
Examples:
- `versionTimeString` is a plain `string`, but the required format is actually meaningful.
- `type` is a plain string, but it behaves like a discriminator.
- nested arrays are documented in PHPDoc, but not enforced structurally by the generic `FromFlatArrayTrait`.

#### b) Public mutable properties allow invalid intermediate states
The current model allows objects to exist partially initialized or mutated into invalid states after hydration.

That may be acceptable for a lightweight DTO library, but it weakens the DTOs as a formal specification.

#### c) Array hydration is flat, but the API is hierarchical
`FromFlatArrayTrait` is useful for simple flat payloads, but publication data is expected to become nested and structured.

For the API definition to remain reliable when transcription/edition structures grow, the contract needs first-class support for:
- nested objects,
- arrays of objects,
- discriminated unions,
- required/optional fields,
- and field-level validation.

### Recommendation
Evolve the DTO layer from "typed bags of fields" into a more explicit schema model.

---

## 2.4 Formal API description is currently missing

The README gives a helpful narrative description, but it is not formal enough for:
- automated validation,
- client generation in other languages,
- change detection,
- or machine-readable schema documentation.

### Recommendation
Keep PHP classes as the source of truth, but generate at least one formal representation from them:
- **OpenAPI 3.1** for HTTP/API tooling and documentation
- **JSON Schema** for payload validation
- optionally, generated Markdown docs

This can be done without giving up PHP as the primary definition language.

---

## 2.5 Test coverage is too shallow for a client library

There are currently only two tests, both happy-path oriented.

That is enough for smoke testing, but not enough for a client whose main job is to interpret remote responses safely.

### Recommendation
Most future testing effort should go into failure modes, schema edge cases, and round-trip contract behavior.

---

## 3. Suggested improvements

Below is a prioritized list of concrete improvements.

---

## Priority 1 — Make the client robust and predictable

### 3.1 Use strict JSON decoding
Use:
- `json_decode($body, true, flags: JSON_THROW_ON_ERROR)`

and catch `JsonException`.

This gives clean handling for invalid or truncated JSON.

### 3.2 Validate decoded payload shape before reading keys
Before accessing fields like `result`, `publicationData`, or `publications`, validate:
- that the decoded value is an array,
- that required keys exist,
- that values have the expected top-level types.

At minimum, add dedicated private validators/factories such as:
- `parseListResponse(array $data): PublicationApiListResponse|ErrorResponse`
- `parseGetResponse(array $data): PublicationApiGetResponse|ErrorResponse`

That will make the parsing logic easier to test separately.

### 3.3 Handle API-level error responses consistently in both endpoints
`get()` should, like `list()`, first detect whether the server returned an API error payload.

The handling of `result === Error` should be symmetric between endpoints.

### 3.4 Distinguish remote API errors from local client failures
Current options:

#### Option A: keep the current return style
Keep returning `ErrorResponse`, but enrich it with a local classification layer, e.g.:
- transport error
- invalid JSON
- invalid server payload
- remote API error

This is the least disruptive option.

#### Option B: separate concerns more cleanly
- Return only valid API response DTOs from successful HTTP calls
- Throw dedicated client exceptions for:
  - transport failure
  - decode failure
  - protocol violation

This is conceptually cleaner for a library client.

### Recommendation
If backwards compatibility matters, choose **Option A now**, but make the internal distinction explicit and documented.

### 3.5 Preserve HTTP details on failures
Where possible, preserve:
- HTTP status code
- response body (or a safe excerpt)
- original exception message
- request URI

This will make the client much easier to debug in real integrations.

### 3.6 Clarify base URI and path construction
The README mentions routes like:
- `api/publication/list`
- `api/publication/{id}/get`

but the client calls:
- `list`
- `{id}/get`

This implies that the caller must configure the Guzzle base URI to the publication API root.

That assumption should be made explicit.

Possible improvements:
- document it clearly, or
- accept a base URL / route prefix in the client constructor, or
- provide a small factory that builds the underlying HTTP client correctly.

### 3.7 Add `declare(strict_types=1);`
This is a small but worthwhile improvement for a contract-oriented PHP library.

---

## Priority 2 — Strengthen the PHP contract model

### 3.8 Replace publication type string constants with a backed enum
Replace:
- `PublicationType` constants class

with something like:
- `enum PublicationType: string`

Benefits:
- stronger typing
- fewer invalid values
- better discriminator handling
- better fit for formal schema generation

### 3.9 Introduce explicit wire-format factories and serializers
Each contract class should ideally support both directions:
- `fromArray()` / `fromWire()`
- `toArray()` / `toWire()`

This has several benefits:
- enables round-trip testing
- enables schema generation
- makes the contract definition more explicit
- helps server and client implementations stay aligned

### 3.10 Add field-level validation where semantics matter
Some fields are more than primitive types.

Examples:
- `versionTimeString` should conform to the documented timestamp format
- `type` should match a known publication type
- nested collection fields should contain the expected item shape

A good near-term improvement would be to validate `versionTimeString` using the already-installed `thomas-institut/timestring` package.

### 3.11 Define required vs optional fields explicitly
The current `FromFlatArrayTrait` infers required fields from the absence of default values.

That is clever and lightweight, but not explicit enough for long-term schema evolution.

Suggested improvement:
- make required/optional status explicit in code, either through
  - constructors,
  - attributes,
  - or dedicated schema metadata.

### 3.12 Add support for nested object hydration in a first-class way
The current flat trait will not be enough once the hierarchical publication structures are fully used.

Suggested direction:
- add a generic nested hydration mechanism, or
- implement explicit `fromArray()` methods for structured DTOs, or
- use attributes to declare item types for array properties.

For example, arrays like `pages` and `columns` should be able to declare their element classes in a way that is both:
- runtime-usable, and
- schema-generatable.

### 3.13 Consider making DTOs immutable or at least validation-friendly
There are three reasonable options:

#### Option A: keep mutable public properties
Fastest and simplest, but weakest contract.

#### Option B: use named constructors/static factories and readonly properties
Best for contract reliability.

#### Option C: hybrid approach
- keep properties public for compatibility
- add `validate()` and `toArray()` methods
- gradually move new types to readonly construction

### Recommendation
For this project, **Option C** may be the best incremental path unless a breaking change is acceptable.

---

## Priority 3 — Make the API formally describable from PHP

### 3.14 Add contract metadata through PHP attributes
If PHP is to remain the source of truth, attributes are a strong fit.

Examples of useful metadata:
- field description
- example value
- required/optional
- wire format (`date-time`, custom `timestring`, etc.)
- discriminator mapping
- array item type
- endpoint method/path/summary

Illustrative direction:

```php
#[ApiSchema(name: 'PublicationListing')]
final class PublicationListing
{
    #[ApiField(description: 'Publication type discriminator', example: 'text')]
    public PublicationType $type;

    #[ApiField(description: 'Publication id', example: 123)]
    public int $id;

    #[ApiField(description: 'Version timestamp', format: 'apm-timestring', example: '2026-01-20 15:23:20.123456')]
    public string $versionTimeString;
}
```

This would allow generation of:
- Markdown docs
- JSON Schema
- OpenAPI components

while keeping the PHP class authoritative.

### 3.15 Define endpoints formally in code
In addition to DTOs, the HTTP layer itself should be defined in code.

Examples of metadata to capture:
- method (`GET`)
- path (`/list`, `/{id}/get`)
- path params
- success response class
- error response class
- endpoint summary/description

This could be done via:
- endpoint descriptor classes, or
- attributes on client methods.

### 3.16 Generate OpenAPI 3.1 from the PHP definitions
This is likely the most valuable formal artifact to add.

Why OpenAPI here:
- widely supported
- describes paths and schemas together
- useful for external consumers
- can be rendered as docs
- can be used for validation and tooling

Important point: the OpenAPI file should be **generated**, not treated as the hand-edited source of truth.

### 3.17 Generate JSON Schema for DTO payloads
OpenAPI is best at the HTTP/API level.
JSON Schema is useful at the payload level.

A generated JSON Schema for each response/data type would help with:
- automated validation in tests
- interoperability with non-PHP consumers
- change review during releases

### 3.18 Use discriminator-based schema modeling for `publicationData`
`publicationData` is a classic discriminated union keyed by `type`.

That should be modeled explicitly in both PHP and generated schema artifacts.

In OpenAPI terms, this suggests:
- `oneOf`
- plus a discriminator on `type`

In PHP terms, this suggests:
- enum-backed type discriminator
- registry/mapping from type => DTO class

---

## Priority 4 — Improve architecture for future publication types

### 3.19 Replace `switch`-based publication type parsing with a registry
Today, `PublicationApiClient::get()` switches on `publicationData['type']`.

This will become harder to maintain as publication types grow.

Suggested replacement:
- a map from `PublicationType` => DTO class / hydrator

For example:

```php
[
    PublicationType::Text => TextPublicationData::class,
    PublicationType::Transcription => TranscriptionData::class,
]
```

Benefits:
- simpler extension
- less branching logic
- central contract registry
- easier schema generation

### 3.20 Separate transport concerns from parsing concerns
`PublicationApiClient` currently does:
- URL construction
- HTTP calling
- JSON decoding
- error mapping
- DTO hydration

That is workable now, but it mixes concerns.

A better split would be:
- **HTTP transport/client**
- **response parser/hydrator**
- optionally **schema/contract registry**

This will make future additions easier and reduce the chance of inconsistent behavior between endpoints.

### 3.21 Consider PSR-18 instead of hard-coding Guzzle in the public API
Depending directly on `GuzzleHttp\Client` is convenient, but coupling the public client API to Guzzle reduces flexibility.

A more portable library surface would accept:
- `Psr\Http\Client\ClientInterface`
- a request factory
- optionally a URI factory

This would still allow Guzzle internally, but avoid making Guzzle the required public abstraction.

This is not urgent, but it would improve interoperability.

---

## Priority 5 — Improve tests and quality gates

### 3.22 Add negative tests for all major failure modes
Suggested test cases:
- invalid JSON
- missing top-level keys
- `publications` is not an array
- publication item missing a required field
- publication item field of wrong type
- `get()` returns API `Error`
- unsupported publication type
- malformed `publicationData`
- transport exception
- HTTP exception with status code

### 3.23 Assert response types explicitly in tests
The current tests assume success and then access properties directly.

Improve tests by first asserting the concrete returned type:
- `PublicationApiListResponse`
- `PublicationApiGetResponse`
- `ErrorResponse`

This makes the intended contract clearer.

### 3.24 Add request-path assertions
Tests should verify not only parsed responses but also request behavior.

For example, assert that the client actually calls:
- `list`
- `{id}/get`

Guzzle middleware/history can help here.

### 3.25 Add round-trip tests once serializers exist
For DTOs that become the contract source of truth, round-trip tests are very valuable:
- `array -> DTO -> array`
- and, where relevant, `DTO -> JSON Schema/OpenAPI generation`

### 3.26 Add CI if not already present externally
A small library like this should ideally run at least:
- tests
- static analysis

on every change.

---

## 4. Specific code-level suggestions

## 4.1 `PublicationApiClient`
Suggested improvements:
- handle `JsonException`
- validate that decoded data is an array
- unify top-level error handling between `list()` and `get()`
- preserve HTTP status from request exceptions
- fix unsupported-type error message to read from `publicationData.type`
- extract parsing into private methods or a dedicated parser class
- consider adding a registry for publication-type hydration

## 4.2 `PublicationType`
Suggested improvements:
- convert to `enum PublicationType: string`
- use it both in DTOs and in parser dispatch
- use it as the discriminator basis for generated schemas

## 4.3 `PublicationListing` / `PublicationData`
Suggested improvements:
- formalize field semantics with metadata
- add serialization support (`toArray()`)
- consider validating `versionTimeString`
- consider whether `type` should be enum-typed instead of plain string

## 4.4 Structured publication DTOs
Suggested improvements:
- add explicit nested hydration strategy
- validate array item classes and shapes
- ensure future complex publication types remain schema-generatable

## 4.5 README
Suggested improvements:
- clarify base URI assumptions
- document exact response envelope fields
- document the canonical timestamp format
- explain error behavior
- state whether all API responses always use the StandardApi envelope
- link to any generated formal schema artifacts once added

---

## 5. Suggested roadmap

## Phase 1 — Robustness and correctness
1. Harden JSON decoding and payload validation
2. Fix inconsistent error handling in `get()`
3. Preserve HTTP status and error context
4. Add tests for malformed and error responses
5. Add `strict_types`

## Phase 2 — Contract strengthening
1. Introduce `PublicationType` enum
2. Add `toArray()` / explicit serializers
3. Add validation for semantic fields like `versionTimeString`
4. Introduce nested hydration strategy for structured DTOs
5. Add registry-based publication type dispatch

## Phase 3 — Formal API artifacts from PHP
1. Add PHP attributes or equivalent metadata
2. Build schema generator for DTOs
3. Generate JSON Schema
4. Generate OpenAPI 3.1
5. Generate Markdown API reference from the same metadata

## Phase 4 — Library ergonomics and interoperability
1. Consider PSR-18-based client surface
2. Improve exception/result model
3. Add CI and contract regression checks
4. Add release-time schema diffing/versioning rules

---

## 6. Recommended target state

A strong target state for this repository would be:

- **PHP classes remain the canonical API definition**
- DTOs are rich enough to express:
  - required fields
  - optional fields
  - discriminators
  - nested object types
  - field descriptions/formats/examples
- a generator produces:
  - OpenAPI 3.1
  - JSON Schema
  - Markdown docs
- the client:
  - distinguishes remote API errors from local client failures
  - validates payloads strictly
  - supports all publication types through a registry
  - preserves HTTP and parsing error context
- tests cover both happy paths and malformed/error responses thoroughly

That combination would satisfy both goals well:
1. **a clear API definition**, and
2. **a robust official PHP client**.

---

## 7. Condensed priority list

If only a short action list is needed, the highest-value improvements are:

1. Add strict JSON decoding and top-level payload validation.
2. Make `get()` handle API error payloads the same way as `list()`.
3. Preserve HTTP status and distinguish server errors from local client failures.
4. Replace publication type string constants with a backed enum.
5. Add serializer methods and explicit nested hydration support.
6. Add metadata/attributes so PHP classes can generate formal API docs.
7. Generate OpenAPI 3.1 and JSON Schema from the PHP definitions.
8. Expand tests heavily around failure modes and schema validation.
9. Clarify the base URI/path contract in README and generated docs.
10. Introduce a publication-type registry for scalable parsing and schema generation.
