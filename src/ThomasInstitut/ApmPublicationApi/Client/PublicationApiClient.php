<?php

namespace ThomasInstitut\ApmPublicationApi\Client;

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
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private string $baseUrl
    ) {
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
            $apiResponse->result = $data['result'] ?? ApiResponse::ResultUndefined;
            $apiResponse->timeStamp = $data['timeStamp'] ?? -1;
            $apiResponse->publications = [];
            if (!isset($data['publications']) || !is_array($data['publications'])) {
                throw new InvalidResponseFromServerException("Invalid response from server: no publication array");
            }
            foreach( $data['publications'] as $publication) {
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
            $apiResponse->result = $data['result'] ?? ApiResponse::ResultUndefined;
            $apiResponse->timeStamp = $data['timeStamp'] ?? -1;
            if (!isset($data['publicationData']) || !is_array($data['publicationData']) || !isset($data['publicationData']['type'])) {
                throw new InvalidResponseFromServerException("Invalid response from server: no publication type");
            }
            switch ($data['publicationData']['type']) {
                case PublicationType::Text:
                    $apiResponse->publicationData = new TextPublicationData();
                    $apiResponse->publicationData->fromArray($data['publicationData']);
                    break;

                default:
                    throw new InvalidResponseFromServerException("Invalid publication type: {$data['publicationData']['type']}");
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
     */
    private function parseAndValidateResponse(string $jsonBody): array
    {
        try {
            $data = json_decode($jsonBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidResponseFromServerException("Invalid JSON response from server: " . $e->getMessage(), $e->getCode(), $e);
        }

        if (!is_array($data)) {
            throw new InvalidResponseFromServerException("Invalid response from server: expected JSON object or array");
        }

        if (($data['result'] ?? ApiResponse::ResultUndefined) === ApiResponse::ResultError) {
            throw new InvalidResponseFromServerException($data['message'] ?? 'Unknown server error');
        }

        return $data;
    }
}