# APM Publication API

This repo provides the official definition of APM's publication API in PHP and a client implementation. 

> This repo is still **alpha**. DO NOT assume any information or code here will stay as it is right now.

## API Description

The Publication API is how APM publishes its data to external clients, most notably APE. APM users determine which
resources they want to make available to specific clients, and the clients call the API to get a listing of these
resources and to get the data.

There are two calls:

- `api/publication/list`: returns a StandardApiResponse with an array of PublicationListing objects
- `api/publication/{id}/get`: returns a StandardApiResponse with the data for the given id.

A publication listing consists of general information about a publication:

- type: for example, `'Transcription'`
- id
- versionTimeString
- title
- description

The data for a publication contains this same information together with all required resource's data according to the
publication type.

## Publication Types

### Text

A text string without any formatting.

### Transcription

The transcription of a document, normally a manuscript. It consists of an array of pages, each one with a number of
columns, with each column containing a transcription. A column transcription consists of an array of elements (main
text, marginal additions, etc.). Each element in turn consists of an array of transcription items: simple text,
abbreviations, additions, etc.

### Edition

*TBD*

## Api Client

The `PublicationApiClient` requires a PSR-18 HTTP Client and a PSR-17 Request Factory. Below is an example of how to use it with [Guzzle](https://github.com/guzzle/guzzle).

### Installation

If you don't have an HTTP client yet, you can install Guzzle:

```bash
composer require guzzlehttp/guzzle
```

### Usage Example

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use ThomasInstitut\ApmPublicationApi\Client\PublicationApiClient;

// 1. Create the PSR-18 HTTP Client (Guzzle 7+ implements this)
$httpClient = new Client();

// 2. Create the PSR-17 Request Factory (Guzzle PSR-7 provides this)
$requestFactory = new HttpFactory();

// 3. Define the base URL of the APM API (without /publication)
$baseUrl = 'https://apm.example.com/api';

// 4. Initialize the PublicationApiClient
$apiClient = new PublicationApiClient(
    $httpClient,
    $requestFactory,
    $baseUrl
);

// Optional: you can also provide a logger and enable debug mode
// $apiClient = new PublicationApiClient($httpClient, $requestFactory, $baseUrl, $logger, true);

// Example usage:
try {
    $listResponse = $apiClient->list();
    foreach ($listResponse->publications as $listing) {
        echo $listing->title . PHP_EOL;
    }
} catch (\ThomasInstitut\ApmPublicationApi\Client\PublicationApiClientException $e) {
    // Handle transport or server errors
}
```

### Key Components
- **`GuzzleHttp\Client`**: Implements `Psr\Http\Client\ClientInterface`.
- **`GuzzleHttp\Psr7\HttpFactory`**: Implements `Psr\Http\Message\RequestFactoryInterface`.
- **`baseUrl`**: The base URL for the APM API (e.g., `https://example.com/api`). The client automatically appends `/publication/list` or `/publication/{id}/get` to this URL.
- **`logger`**: (Optional) A `Psr\Log\LoggerInterface` to log API requests and responses. Defaults to `NullLogger`.
- **`debug`**: (Optional) Set to `true` to enable detailed debug logging. Defaults to `false`.


