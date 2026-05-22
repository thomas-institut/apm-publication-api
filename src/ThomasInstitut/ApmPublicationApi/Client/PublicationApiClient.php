<?php

namespace ThomasInstitut\ApmPublicationApi\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
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
    public function __construct(private GuzzleClient $client)
    {
    }

    /**
     * @throws InvalidResponseFromServerException
     * @throws HttpClientException
     */
    public function list(): PublicationApiListResponse
    {
        $url = 'list';
        try {
            $response = $this->client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['result'] === ApiResponse::ResultError) {
                throw new InvalidResponseFromServerException($data['message'] ?? 'Unknown server error');
            }
            $apiResponse = new PublicationApiListResponse();
            $apiResponse->result = $data['result'] ?? ApiResponse::ResultUndefined;
            $apiResponse->timeStamp = $data['timeStamp'] ?? -1;
            $apiResponse->publications = [];
            if (!isset($data['publications'])) {
                throw new InvalidResponseFromServerException("Invalid response from server: no publication array");
            }
            foreach( $data['publications'] as $publication) {
                $pubObject = new PublicationListing();
                $pubObject->fromArray($publication);
                $apiResponse->publications[] = $pubObject;
            }
            return $apiResponse;
        } catch (GuzzleException $e) {
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
        $url =  "$id/get";
        try {
            $response = $this->client->get($url);
            $data = json_decode($response->getBody()->getContents(), true);

            $apiResponse = new PublicationApiGetResponse();
            $apiResponse->result = $data['result'] ?? ApiResponse::ResultUndefined;
            $apiResponse->timeStamp = $data['timeStamp'] ?? -1;
            if (!isset($data['publicationData']) || !isset($data['publicationData']['type'])) {
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
        } catch (GuzzleException $e) {
            throw new HttpClientException("Guzzle error: " . $e->getMessage(), $e->getCode(), $e);
        } catch (MissingRequiredValueException|WrongValueTypeException $e) {
            throw new InvalidResponseFromServerException("Server response is invalid: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}