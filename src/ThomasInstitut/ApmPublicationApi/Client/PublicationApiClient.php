<?php

namespace ThomasInstitut\ApmPublicationApi\Client;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ThomasInstitut\ApmPublicationApi\PublicationApiGetResponse;
use ThomasInstitut\ApmPublicationApi\PublicationApiListResponse;
use ThomasInstitut\ApmPublicationApi\PublicationListing;
use ThomasInstitut\ApmPublicationApi\PublicationType;
use ThomasInstitut\ApmPublicationApi\TextPublicationData;
use ThomasInstitut\ApmPublicationApi\TranscriptionData;
use ThomasInstitut\StandardApi\ApiResponse;
use ThomasInstitut\StandardApi\ApiResult;

readonly class PublicationApiClient
{

    private TreeMapper $mapper;
    public function __construct(
        private ClientInterface         $client,
        private RequestFactoryInterface $requestFactory,
        private string                  $baseUrl,
        private LoggerInterface         $logger = new NullLogger(),
        private bool                    $debug = false
    )
    {
        class_exists(ApiResponse::class);
        $this->mapper = (new MapperBuilder())->mapper();
    }

    /**
     * @throws InvalidResponseFromServerException
     * @throws HttpClientException
     * @throws NotFoundException
     */
    public function list(): PublicationApiListResponse
    {
        $url = rtrim($this->baseUrl, '/') . '/publication/list';
        try {
            $request = $this->requestFactory->createRequest('GET', $url);
            $response = $this->client->sendRequest($request);
            if ($response->getStatusCode() !== 200) {
                if ($response->getStatusCode() === 404) {
                    throw new NotFoundException("Publication not found");
                }
                throw new HttpClientException("Http client error: " . $response->getReasonPhrase());
            }
            $data = $this->parseAndValidateResponse($response->getBody()->getContents());
            $this->debug && $this->logger->debug("Publication API response for 'list': ", $data);

            $apiResponse = new PublicationApiListResponse();
            $this->hydrateBaseResponse($apiResponse, $data);

            if (!isset($data['publications']) || !is_array($data['publications'])) {
                throw new InvalidResponseFromServerException("Invalid response from server: no publications array");
            }

            try {
                /** @var array<PublicationListing> $publications */
                $publications = $this->mapper->map(
                    'array<' . PublicationListing::class . '>',
                    $data['publications']
                );
                $apiResponse->publications = $publications;
            } catch (MappingError $e) {
                $this->debug && $this->logger->debug("Mapping error in 'list': ", [ ...$e->messages()]);
                throw new InvalidResponseFromServerException("Server response is invalid: " . $e->getMessage(), 0, $e);
            }
            return $apiResponse;
        } catch (ClientExceptionInterface $e) {
            throw new HttpClientException("Http client error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws InvalidResponseFromServerException
     * @throws HttpClientException|NotFoundException
     */
    public function get(int $id): PublicationApiGetResponse
    {
        $url = rtrim($this->baseUrl, '/') . "/publication/$id/get";
        try {
            $request = $this->requestFactory->createRequest('GET', $url);
            $response = $this->client->sendRequest($request);
            if ($response->getStatusCode() !== 200) {
                if ($response->getStatusCode() === 404) {
                    throw new NotFoundException("Publication not found");
                }
                throw new HttpClientException("Http client error: " . $response->getReasonPhrase());
            }
            $data = $this->parseAndValidateResponse($response->getBody()->getContents());
            $this->debug && $this->logger->debug("Publication API response for 'get $id': ", $data);

            $apiResponse = new PublicationApiGetResponse();
            $this->hydrateBaseResponse($apiResponse, $data);

            if (!isset($data['publicationData']) || !is_array($data['publicationData']) || !isset($data['publicationData']['type'])) {
                throw new InvalidResponseFromServerException("Invalid response from server: no publication type");
            }
            $type = is_scalar($data['publicationData']['type']) ? (string)$data['publicationData']['type'] : '';

            try {
                $apiResponse->publicationData = match ($type) {
                    PublicationType::Text->value => $this->mapper->map(
                        TextPublicationData::class,
                        $data['publicationData']
                    ),
                    PublicationType::Transcription->value => $this->mapper->map(
                        TranscriptionData::class,
                        $data['publicationData']
                    ),
                    default => throw new InvalidResponseFromServerException("Invalid publication type: $type"),
                };
            } catch (MappingError $e) {
                $this->debug && $this->logger->debug("Mapping error in 'get $id': ", [ ...$e->messages()]);
                throw new InvalidResponseFromServerException("Server response is invalid: " . $e->getMessage(), 0, $e);
            }
            return $apiResponse;
        } catch (ClientExceptionInterface $e) {
            throw new HttpClientException("Http client error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return array<string, mixed>
     * @throws InvalidResponseFromServerException
     */
    private function parseAndValidateResponse(string $jsonBody): array
    {
        try {
            $data = json_decode($jsonBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidResponseFromServerException("Invalid JSON response from server: " . $e->getMessage(), $e->getCode(), $e);
        }
        if (!is_array($data)) {
            throw new InvalidResponseFromServerException("Invalid response from server: expected JSON object or array");
        }
        if (!$this->onlyHasStringKeys($data)) {
            throw new InvalidResponseFromServerException("Invalid response from server: expected JSON object with string keys");
        }
        /** @var array<string, mixed> $data */  // this is guaranteed by the previous check
        if (($data['result'] ?? ApiResult::Undefined->value) === ApiResult::Error->value) {
            $message = is_scalar($data['message'] ?? null) ? (string)$data['message'] : 'Unknown server error';
            throw new InvalidResponseFromServerException($message);
        }

        return $data;
    }

    /**
     * @param PublicationApiListResponse|PublicationApiGetResponse $apiResponse
     * @param array<string, mixed> $data
     * @throws InvalidResponseFromServerException
     */
    private function hydrateBaseResponse(PublicationApiListResponse|PublicationApiGetResponse $apiResponse, array $data): void
    {
        $apiResponse->result = isset($data['result']) && is_string($data['result']) ? ApiResult::from($data['result']) : ApiResult::Undefined;
        if ($apiResponse->result === ApiResult::Undefined) {
            throw new InvalidResponseFromServerException("Invalid response from server: no result");
        }
        $apiResponse->timeStamp = is_numeric($data['timeStamp'] ?? null) ? (int)$data['timeStamp'] : -1;
        if ($apiResponse->timeStamp === -1) {
            throw new InvalidResponseFromServerException("Invalid response from server: no timestamp");
        }
    }

    /**
     * @param array<int|string, mixed> $array
     * @return bool
     */
    private function onlyHasStringKeys(array $array): bool
    {
        foreach (array_keys($array) as $key) {
            if (!is_string($key)) {
                return false;
            }
        }
        return true;
    }
}