# AGENTS.md

## Purpose
This repository provides the PHP definition of APM's publication API together with a small HTTP client implementation.

The package is still early-stage / alpha. Prefer small, conservative changes that preserve the current public API unless the task explicitly asks for broader refactoring.

## Tech stack
- PHP 8.3 target (`composer.json` platform)
- Composer package
- PHPUnit 12 for tests
- PHPStan 2 (level 4)
- Guzzle 7 for HTTP
- `thomas-institut/php-backend-common` for `StandardApi` response types and `Settable` array hydration helpers

## Repository layout
- `src/ThomasInstitut/ApmPublicationApi/`
  - `PublicationApiClient.php`: main HTTP client
  - `PublicationApiListResponse.php`, `PublicationApiGetResponse.php`: API response DTOs
  - `PublicationListing.php`, `PublicationData.php`: shared publication DTOs
  - `TextPublicationData.php`, `TranscriptionData.php`, `TranscriptionPage.php`, `TranscriptionColumn.php`: publication-type-specific DTOs
  - `PublicationType.php`: string constants for supported publication types
- `test/ThomasInstitut/ApmPublicationApi/PublicationApiClientTest.php`: current unit tests
- `README.md`: package overview and API description
- `phpunit.xml`, `phpstan.neon`: test and static analysis config

## Current architecture
### API shape
The client expects two endpoints relative to a configured Guzzle base URI:
- `list`
- `{id}/get`

### Main flow
- `PublicationApiClient::list()`
  - calls `GET list`
  - decodes JSON
  - throws `ThomasInstitut\ApmPublicationApi\Client\HttpClientException` on transport errors
  - throws `ThomasInstitut\ApmPublicationApi\Client\InvalidResponseFromServerException` on server-declared errors or invalid payloads
  - otherwise hydrates a `PublicationApiListResponse` containing `PublicationListing[]`

- `PublicationApiClient::get(int $id)`
  - calls `GET {id}/get`
  - decodes JSON
  - validates `publicationData.type`
  - currently hydrates only `TextPublicationData`
  - throws `ThomasInstitut\ApmPublicationApi\Client\HttpClientException` on transport errors
  - throws `ThomasInstitut\ApmPublicationApi\Client\InvalidResponseFromServerException` on unsupported/invalid cases or server-declared errors

### Hydration model
- `PublicationListing` uses `FromFlatArrayTrait` from the shared dependency.
- `PublicationData` extends `PublicationListing`.
- `TextPublicationData` overrides `fromArray()` to populate `text` after the shared fields.
- `TranscriptionData`, `TranscriptionPage`, and `TranscriptionColumn` currently exist as structure definitions only; they are not yet hydrated by the client.

## Important implementation notes
- Keep namespace/path alignment exact: `ThomasInstitut\...` maps to `src/ThomasInstitut/`.
- This codebase uses simple public-property DTOs rather than immutable value objects.
- Error handling uses exceptions:
  - success: `PublicationApiListResponse` / `PublicationApiGetResponse`
  - failure: throws `PublicationApiClientException` (or specific subclasses)
- The tests use Guzzle's `MockHandler`; follow that pattern for client tests.

## Development commands
Run from repo root:
- `composer test`
- `composer phpstan`
- `composer test:coverage`

Current baseline: tests and PHPStan pass.

## Testing expectations
When changing PHP code, run at least:
- `composer test`
- `composer phpstan`

Add or update tests when modifying:
- response parsing
- supported publication types
- error handling branches
- DTO hydration behavior

## Known limitations / caveats
- The README describes multiple publication types, but `PublicationApiClient::get()` currently supports only `PublicationType::Text`.
- Transcription DTOs are incomplete placeholders from a hydration/usage perspective.
- `PublicationApiClient` assumes decoded JSON is in the expected associative-array shape; it does not currently validate every malformed JSON case.
- There is a likely small bug in the unsupported type branch of `PublicationApiClient::get()`: the error message references `$data['type']` instead of `publicationData.type`.

## Guidance for future agents
- Prefer minimal edits to large refactors.
- Preserve public class names, namespaces, and response shapes unless the task explicitly calls for breaking changes.
- If adding publication types, update all of:
  - DTO classes
  - `PublicationType`
  - `PublicationApiClient::get()` dispatch/hydration
  - tests
- If improving validation, keep returned error behavior consistent with existing `ErrorResponse` usage.
