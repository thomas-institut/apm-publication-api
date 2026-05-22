<?php

namespace ThomasInstitut\ApmPublicationApi\Client;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use ThomasInstitut\ApmPublicationApi\PublicationApiGetResponse;
use ThomasInstitut\ApmPublicationApi\PublicationApiListResponse;
use ThomasInstitut\ApmPublicationApi\PublicationListing;
use ThomasInstitut\ApmPublicationApi\PublicationType;
use ThomasInstitut\ApmPublicationApi\TextPublicationData;
use ThomasInstitut\Settable\MissingRequiredValueException;
use ThomasInstitut\Settable\WrongValueTypeException;
use ThomasInstitut\StandardApi\ApiResponse;

readonly class PublicationApiClient
{
    public function __construct(
        private ClientInterface         $client,
        private RequestFactoryInterface $requestFactory,
        private string                  $baseUrl
    )
    {
    }

    /**
     * @throws InvalidResponseFromServerException
     * @throws HttpClientException
     */
    public function list(): PublicationApiListResponse
    {
        $url = rtrim($this->baseUrl, '/') . '/publication/list';
        try {
            $request = $this->requestFactory->createRequest('GET', $url);
            $response = $this->client->sendRequest($request);
            $data = $this->parseAndValidateResponse($response->getBody()->getContents());

            $apiResponse = new PublicationApiListResponse();
            $this->hydrateBaseResponse($apiResponse, $data);

            $apiResponse->publications = [];
            if (!isset($data['publications']) || !is_array($data['publications'])) {
                throw new InvalidResponseFromServerException("Invalid response from server: no publication array");
            }
            foreach ($data['publications'] as $publication) {
                if (!is_array($publication)) {
                    throw new InvalidResponseFromServerException("Invalid response from server: publication is not an array");
                }
                $pubObject = new PublicationListing();
                $pubObject->fromArray($publication);
                $apiResponse->publications[] = $pubObject;
            }
            return $apiResponse;
        } catch (ClientExceptionInterface $e) {
            throw new HttpClientException("Http client error: " . $e->getMessage(), $e->getCode(), $e);
        } catch (MissingRequiredValueException|WrongValueTypeException $e) {
            throw new InvalidResponseFromServerException("Server response is invalid: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws InvalidResponseFromServerException
     * @throws HttpClientException
     */
    public function get(int $id): PublicationApiGetResponse
    {
        $url = rtrim($this->baseUrl, '/') . "/publication/$id/get";
        try {
            $request = $this->requestFactory->createRequest('GET', $url);
            $response = $this->client->sendRequest($request);
            $data = $this->parseAndValidateResponse($response->getBody()->getContents());

            $apiResponse = new PublicationApiGetResponse();
            $this->hydrateBaseResponse($apiResponse, $data);

            if (!isset($data['publicationData']) || !is_array($data['publicationData']) || !isset($data['publicationData']['type'])) {
                throw new InvalidResponseFromServerException("Invalid response from server: no publication type");
            }
            $type = is_scalar($data['publicationData']['type']) ? (string)$data['publicationData']['type'] : '';
            switch ($type) {
                case PublicationType::Text:
                    $apiResponse->publicationData = new TextPublicationData();
                    /** @var array<string, mixed> $publicationData */
                    $publicationData = $data['publicationData'];
                    $apiResponse->publicationData->fromArray($publicationData);
                    break;

                default:
                    throw new InvalidResponseFromServerException("Invalid publication type: $type");
            }
            return $apiResponse;
        } catch (ClientExceptionInterface $e) {
            throw new HttpClientException("Http client error: " . $e->getMessage(), $e->getCode(), $e);
        } catch (MissingRequiredValueException|WrongValueTypeException $e) {
            throw new InvalidResponseFromServerException("Server response is invalid: " . $e->getMessage(), $e->getCode(), $e);
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
        if (($data['result'] ?? ApiResponse::ResultUndefined) === ApiResponse::ResultError) {
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
        $apiResponse->result = is_scalar($data['result'] ?? null) ? (string)$data['result'] : ApiResponse::ResultUndefined;
        if ($apiResponse->result === ApiResponse::ResultUndefined) {
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