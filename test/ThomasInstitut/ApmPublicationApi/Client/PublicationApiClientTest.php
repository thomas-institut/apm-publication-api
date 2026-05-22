<?php

namespace ThomasInstitut\ApmPublicationApi\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ThomasInstitut\ApmPublicationApi\PublicationListing;
use ThomasInstitut\ApmPublicationApi\PublicationType;
use ThomasInstitut\ApmPublicationApi\TextPublicationData;
use ThomasInstitut\Settable\MissingRequiredValueException;
use ThomasInstitut\Settable\WrongValueTypeException;
use ThomasInstitut\StandardApi\ApiResponse;

class PublicationApiClientTest extends TestCase
{
    /**
     * @throws InvalidResponseFromServerException
     * @throws HttpClientException
     */
    public function testList(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'result' => ApiResponse::ResultSuccess,
                'timeStamp' => 123456789,
                'publications' => [
                    ['type' => 'test', 'id' => 1, 'versionTimeString' => '2026-01-20 15:23:20.123456', 'title' => 'Test Publication', 'description' => 'This is a test publication'],
                    ['type' => 'test', 'id' => 2, 'versionTimeString' => '2026-01-20 15:23:20.123456', 'title' => 'Another Publication', 'description' => 'Another test publication'],
                    ['type' => 'test', 'id' => 3, 'versionTimeString' => '2026-01-20 15:23:20.123456', 'title' => 'Yet Another Publication', 'description' => 'Yet another test publication']
                ]
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

        $client = new PublicationApiClient($guzzleClient);
        $response = $client->list();

        $this->assertEquals(ApiResponse::ResultSuccess, $response->result, 'Unexpected result: ' . ($response->message ?? ''));
        $this->assertEquals(123456789, $response->timeStamp);
        foreach ($response->publications as $publication) {
            $this->assertInstanceOf(PublicationListing::class, $publication, 'Unexpected publication type: ' . get_class($publication));
        }

    }

    /**
     * @throws HttpClientException
     * @throws InvalidResponseFromServerException
     * @throws MissingRequiredValueException
     * @throws WrongValueTypeException
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

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'result' => 'Success',
                'timeStamp' => 123456789,
                'publicationData' => $publicationData
            ])),
        ]);

        $mockResult = new TextPublicationData();
        $mockResult->fromArray($publicationData);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

        $client = new PublicationApiClient($guzzleClient);
        $response = $client->get(123);

        $this->assertEquals(ApiResponse::ResultSuccess, $response->result, 'Unexpected result: ' . ($response->message ?? ''));
        $this->assertEquals(123456789, $response->timeStamp);
        $this->assertInstanceOf(TextPublicationData::class, $response->publicationData);
    }

    /**
     * @throws HttpClientException
     */
    public function testListThrowsOnServerError(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'result' => ApiResponse::ResultError,
                'message' => 'Something went wrong'
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

        $client = new PublicationApiClient($guzzleClient);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('Something went wrong');
        $client->list();
    }

    /**
     * @throws HttpClientException
     */
    public function testListThrowsOnMissingPublications(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'result' => ApiResponse::ResultSuccess,
                'timeStamp' => 123456789
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

        $client = new PublicationApiClient($guzzleClient);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('no publication array');
        $client->list();
    }

    /**
     * @throws InvalidResponseFromServerException
     */
    public function testListThrowsOnGuzzleException(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection failed', new Request('GET', 'list'))
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

        $client = new PublicationApiClient($guzzleClient);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Connection failed');
        $client->list();
    }

    /**
     * @throws HttpClientException
     */
    public function testGetThrowsOnMissingPublicationData(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'result' => ApiResponse::ResultSuccess,
                'timeStamp' => 123456789
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

        $client = new PublicationApiClient($guzzleClient);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('no publication type');
        $client->get(123);
    }

    /**
     * @throws HttpClientException
     */
    public function testGetThrowsOnInvalidPublicationType(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'result' => ApiResponse::ResultSuccess,
                'timeStamp' => 123456789,
                'publicationData' => [
                    'type' => 'unknown',
                    'id' => 123
                ]
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

        $client = new PublicationApiClient($guzzleClient);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('Invalid publication type: unknown');
        $client->get(123);
    }
}
