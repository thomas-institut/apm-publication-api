<?php

namespace ThomasInstitut\ApmPublicationApi\Client;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use ThomasInstitut\ApmPublicationApi\PublicationListing;
use ThomasInstitut\ApmPublicationApi\PublicationType;
use ThomasInstitut\ApmPublicationApi\TextPublicationData;
use ThomasInstitut\Settable\MissingRequiredValueException;
use ThomasInstitut\Settable\WrongValueTypeException;
use ThomasInstitut\StandardApi\ApiResponse;

class PublicationApiClientTest extends TestCase
{
    private function createClient(mixed $responseData = null, ?\Exception $exception = null): PublicationApiClient
    {
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = $this->createStub(RequestFactoryInterface::class);
        $request = $this->createStub(RequestInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);

        if ($exception) {
            $client->method('sendRequest')->willThrowException($exception);
        } else {
            $response = $this->createStub(ResponseInterface::class);
            $stream = $this->createStub(StreamInterface::class);
            $stream->method('getContents')->willReturn(json_encode($responseData));
            $response->method('getBody')->willReturn($stream);
            $client->method('sendRequest')->willReturn($response);
        }

        return new PublicationApiClient($client, $requestFactory, 'http://api.example.com');
    }

    /**
     * @throws InvalidResponseFromServerException
     * @throws HttpClientException
     */
    public function testList(): void
    {
        $client = $this->createClient([
            'result' => ApiResponse::ResultSuccess,
            'timeStamp' => 123456789,
            'publications' => [
                ['type' => 'test', 'id' => 1, 'versionTimeString' => '2026-01-20 15:23:20.123456', 'title' => 'Test Publication', 'description' => 'This is a test publication'],
                ['type' => 'test', 'id' => 2, 'versionTimeString' => '2026-01-20 15:23:20.123456', 'title' => 'Another Publication', 'description' => 'Another test publication'],
                ['type' => 'test', 'id' => 3, 'versionTimeString' => '2026-01-20 15:23:20.123456', 'title' => 'Yet Another Publication', 'description' => 'Yet another test publication']
            ]
        ]);

        $response = $client->list();

        $this->assertEquals(ApiResponse::ResultSuccess, $response->result);
        $this->assertEquals(123456789, $response->timeStamp);
        foreach ($response->publications as $publication) {
            $this->assertInstanceOf(PublicationListing::class, $publication);
        }
    }

    /**
     * @throws HttpClientException
     * @throws InvalidResponseFromServerException
     */
    public function testGet(): void
    {
        $publicationData = [
            'type' => PublicationType::Text,
            'id' => 123,
            'versionTimeString' => '2026-01-20 15:23:20.123456',
            'title' => 'Test Publication',
            'description' => 'This is a test publication',
            'text' => 'This is the text of the publication.'
        ];

        $client = $this->createClient([
            'result' => ApiResponse::ResultSuccess,
            'timeStamp' => 123456789,
            'publicationData' => $publicationData
        ]);

        $response = $client->get(123);

        $this->assertEquals(ApiResponse::ResultSuccess, $response->result);
        $this->assertEquals(123456789, $response->timeStamp);
        $this->assertInstanceOf(TextPublicationData::class, $response->publicationData);
    }

    /**
     * @throws HttpClientException
     */
    public function testListThrowsOnServerError(): void
    {
        $client = $this->createClient([
            'result' => ApiResponse::ResultError,
            'message' => 'Something went wrong'
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('Something went wrong');
        $client->list();
    }

    /**
     * @throws HttpClientException
     */
    public function testListThrowsOnMissingPublications(): void
    {
        $client = $this->createClient([
            'result' => ApiResponse::ResultSuccess,
            'timeStamp' => 123456789
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('no publication array');
        $client->list();
    }

    /**
     * @throws InvalidResponseFromServerException
     */
    public function testListThrowsOnClientException(): void
    {
        $exception = $this->createStub(ClientExceptionInterface::class);
        // We use an anonymous class to implement the interface and extend Exception
        $exception = new class('Connection failed') extends \Exception implements ClientExceptionInterface {};

        $client = $this->createClient(null, $exception);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Connection failed');
        $client->list();
    }

    /**
     * @throws HttpClientException
     */
    public function testGetThrowsOnMissingPublicationData(): void
    {
        $client = $this->createClient([
            'result' => ApiResponse::ResultSuccess,
            'timeStamp' => 123456789
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('no publication type');
        $client->get(123);
    }

    /**
     * @throws HttpClientException
     */
    public function testGetThrowsOnInvalidPublicationType(): void
    {
        $client = $this->createClient([
            'result' => ApiResponse::ResultSuccess,
            'timeStamp' => 123456789,
            'publicationData' => [
                'type' => 'unknown',
                'id' => 123
            ]
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('Invalid publication type: unknown');
        $client->get(123);
    }
}
