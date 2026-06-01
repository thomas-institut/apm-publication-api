<?php

namespace ThomasInstitut\ApmPublicationApi\Client;

use Exception;
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
use ThomasInstitut\ApmPublicationApi\TranscriptionData;
use ThomasInstitut\ApmPublicationApi\EditionPublication\EditionPublicationData;
use ThomasInstitut\StandardApi\ApiResponse;
use ThomasInstitut\StandardApi\ApiResult;

class PublicationApiClientTest extends TestCase
{
    private function createClient(mixed $responseData = null, ?Exception $exception = null, int $statusCode = 200): PublicationApiClient
    {
        class_exists(ApiResponse::class);
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = $this->createStub(RequestFactoryInterface::class);
        $request = $this->createStub(RequestInterface::class);

        $requestFactory->method('createRequest')->willReturn($request);

        if ($exception) {
            $client->method('sendRequest')->willThrowException($exception);
        } else {
            $response = $this->createStub(ResponseInterface::class);
            $response->method('getStatusCode')->willReturn($statusCode);
            $stream = $this->createStub(StreamInterface::class);
            $content = is_string($responseData) ? $responseData : json_encode($responseData);
            $stream->method('getContents')->willReturn($content);
            $response->method('getBody')->willReturn($stream);
            $client->method('sendRequest')->willReturn($response);
        }

        return new PublicationApiClient($client, $requestFactory, 'https://api.example.com/api');
    }

    /**
     * @throws InvalidResponseFromServerException
     * @throws HttpClientException|NotFoundException
     */
    public function testListUrl(): void
    {
        class_exists(ApiResponse::class);
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = $this->getMockBuilder(RequestFactoryInterface::class)->getMock();
        $request = $this->createStub(RequestInterface::class);
        $response = $this->createStub(ResponseInterface::class);
        $stream = $this->createStub(StreamInterface::class);

        $stream->method('getContents')->willReturn(json_encode([
            'result' => ApiResult::Success->value,
            'timeStamp' => time(),
            'publications' => []
        ]));
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(200);
        $client->method('sendRequest')->willReturn($response);

        $requestFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', 'https://api.example.com/api/publication/list')
            ->willReturn($request);

        $apiClient = new PublicationApiClient($client, $requestFactory, 'https://api.example.com/api');
        $apiClient->list();
    }

    /**
     * @throws InvalidResponseFromServerException
     * @throws HttpClientException|NotFoundException
     */
    public function testGetUrl(): void
    {
        class_exists(ApiResponse::class);
        $client = $this->createStub(ClientInterface::class);
        $requestFactory = $this->getMockBuilder(RequestFactoryInterface::class)->getMock();
        $request = $this->createStub(RequestInterface::class);
        $response = $this->createStub(ResponseInterface::class);
        $stream = $this->createStub(StreamInterface::class);

        $stream->method('getContents')->willReturn(json_encode([
            'result' => ApiResult::Success->value,
            'timeStamp' => time(),
            'publicationData' => [
                'type' => PublicationType::Text->value,
                'id' => 123,
                'versionTimeString' => '2026-01-20 15:23:20.123456',
                'title' => 'Test Publication',
                'description' => 'This is a test publication',
                'text' => 'This is the text of the publication.'
            ]
        ]));
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(200);
        $client->method('sendRequest')->willReturn($response);

        $requestFactory->expects($this->once())
            ->method('createRequest')
            ->with('GET', 'https://api.example.com/api/publication/123/get')
            ->willReturn($request);

        $apiClient = new PublicationApiClient($client, $requestFactory, 'https://api.example.com/api');
        $apiClient->get(123);
    }

    /**
     * @throws InvalidResponseFromServerException
     * @throws HttpClientException|NotFoundException
     */
    public function testList(): void
    {
        $client = $this->createClient([
            'result' => ApiResult::Success->value,
            'timeStamp' => 123456789,
            'publications' => [
                ['type' => PublicationType::Text->value, 'id' => 1, 'versionTimeString' => '2026-01-20 15:23:20.123456', 'title' => 'Test Publication', 'description' => 'This is a test publication'],
                ['type' => PublicationType::Text->value, 'id' => 2, 'versionTimeString' => '2026-01-20 15:23:20.123456', 'title' => 'Another Publication', 'description' => 'Another test publication'],
                ['type' => PublicationType::Text->value, 'id' => 3, 'versionTimeString' => '2026-01-20 15:23:20.123456', 'title' => 'Yet Another Publication', 'description' => 'Yet another test publication']
            ]
        ]);

        $response = $client->list();

        $this->assertEquals(ApiResult::Success, $response->result);
        $this->assertEquals(123456789, $response->timeStamp);
        foreach ($response->publications as $publication) {
            $this->assertInstanceOf(PublicationListing::class, $publication);
        }
    }

    /**
     * @throws HttpClientException
     * @throws InvalidResponseFromServerException|NotFoundException
     */
    public function testGet(): void
    {
        $publicationData = [
            'type' => PublicationType::Text->value,
            'id' => 123,
            'versionTimeString' => '2026-01-20 15:23:20.123456',
            'title' => 'Test Publication',
            'description' => 'This is a test publication',
            'text' => 'This is the text of the publication.'
        ];

        $client = $this->createClient([
            'result' => ApiResult::Success->value,
            'timeStamp' => 123456789,
            'publicationData' => $publicationData
        ]);

        $response = $client->get(123);

        $this->assertEquals(ApiResult::Success, $response->result);
        $this->assertEquals(123456789, $response->timeStamp);
        $this->assertInstanceOf(TextPublicationData::class, $response->publicationData);
    }

    /**
     * @throws HttpClientException
     * @throws InvalidResponseFromServerException
     */
    public function testGetTranscription(): void
    {
        $publicationData = [
            'type' => PublicationType::Transcription->value,
            'id' => 456,
            'versionTimeString' => '2026-01-20 15:23:20.123456',
            'title' => 'Test Transcription',
            'description' => 'This is a test transcription',
            'documentName' => 'Doc 1',
            'docType' => 'Type A',
            'languageCode' => 'de',
            'pages' => [
                [
                    'foliation' => '1r',
                    'pageNumber' => 1,
                    'imageUrl' => 'http://example.com/image1.jpg',
                    'thumbnailUrl' => 'http://example.com/thumb1.jpg',
                    'isTextPage' => true,
                    'columns' => [
                        [
                            'transcriptionText' => 'Some text in col 1'
                        ]
                    ]
                ]
            ]
        ];

        $client = $this->createClient([
            'result' => ApiResult::Success->value,
            'timeStamp' => 123456789,
            'publicationData' => $publicationData
        ]);

        $response = $client->get(456);

        $this->assertEquals(ApiResult::Success, $response->result);
        $this->assertEquals(123456789, $response->timeStamp);
        $this->assertInstanceOf(TranscriptionData::class, $response->publicationData);
        /** @var TranscriptionData $data */
        $data = $response->publicationData;
        $this->assertEquals('Doc 1', $data->documentName);
        $this->assertCount(1, $data->pages);
        $this->assertEquals('1r', $data->pages[0]->foliation);
        $this->assertCount(1, $data->pages[0]->columns);
        $this->assertEquals('Some text in col 1', $data->pages[0]->columns[0]->transcriptionText);
    }

    /**
     * @throws HttpClientException
     * @throws InvalidResponseFromServerException
     */
    public function testGetEdition(): void
    {
        $publicationData = [
            'type' => PublicationType::Edition->value,
            'id' => 789,
            'versionTimeString' => '2026-06-01 10:00:00.000000',
            'title' => 'Test Edition',
            'description' => 'Test Edition Description',
            'languageCode' => 'lat',
            'mainText' => [
                [
                    'type' => 'text',
                    'text' => 'In principio',
                    'style' => 'normal'
                ]
            ],
            'apparatuses' => [],
            'witnesses' => [],
            'siglaGroups' => []
        ];

        $client = $this->createClient([
            'result' => ApiResult::Success->value,
            'timeStamp' => 123456789,
            'publicationData' => $publicationData
        ]);

        $response = $client->get(789);

        $this->assertEquals(ApiResult::Success, $response->result);
        $this->assertInstanceOf(EditionPublicationData::class, $response->publicationData);
        /** @var EditionPublicationData $data */
        $data = $response->publicationData;
        $this->assertEquals('lat', $data->languageCode);
        $this->assertCount(1, $data->mainText);
        $this->assertEquals('In principio', $data->mainText[0]->text);
    }

    /**
     * @throws HttpClientException
     */
    public function testListThrowsOnServerError(): void
    {
        $client = $this->createClient([
            'result' => ApiResult::Error->value,
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
            'result' => ApiResult::Success->value,
            'timeStamp' => 123456789
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('no publications array');
        $client->list();
    }

    /**
     * @throws InvalidResponseFromServerException
     */
    public function testListThrowsOnClientException(): void
    {
        // We use an anonymous class to implement the interface and extend Exception
        $exception = new class('Connection failed') extends Exception implements ClientExceptionInterface {};

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
            'result' => ApiResult::Success->value,
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
            'result' => ApiResult::Success->value,
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

    /**
     * @throws HttpClientException
     */
    public function testListThrowsOnMalformedJson(): void
    {
        $client = $this->createClient("invalid json");

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('Invalid JSON response from server');
        $client->list();
    }

    /**
     * @throws HttpClientException
     */
    public function testGetThrowsOnMalformedJson(): void
    {
        $client = $this->createClient("invalid json");

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('Invalid JSON response from server');
        $client->get(123);
    }

    /**
     * @throws HttpClientException
     */
    public function testGetThrowsOnServerError(): void
    {
        $client = $this->createClient([
            'result' => ApiResult::Error->value,
            'message' => 'Something went wrong on get'
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('Something went wrong on get');
        $client->get(123);
    }

    /**
     * @throws HttpClientException
     */
    public function testListThrowsOnInvalidPublicationsType(): void
    {
        $client = $this->createClient([
            'result' => ApiResult::Success->value,
            'timeStamp' => time(),
            'publications' => 'not an array'
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('no publications array');
        $client->list();
    }

    /**
     * @throws HttpClientException
     */
    public function testListThrowsOnMissingResult(): void
    {
        $client = $this->createClient([
            'timeStamp' => 123456789,
            'publications' => []
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('no result');
        $client->list();
    }

    /**
     * @throws HttpClientException
     */
    public function testListThrowsOnMissingTimestamp(): void
    {
        $client = $this->createClient([
            'result' => ApiResult::Success->value,
            'publications' => []
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('no timestamp');
        $client->list();
    }

    /**
     * @throws HttpClientException
     */
    public function testGetThrowsOnMissingResult(): void
    {
        $client = $this->createClient([
            'timeStamp' => 123456789,
            'publicationData' => [
                'type' => PublicationType::Text->value,
                'id' => 123,
                'versionTimeString' => '2026-01-20 15:23:20.123456',
                'title' => 'Test',
                'description' => 'Test',
                'text' => 'Test'
            ]
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('no result');
        $client->get(123);
    }

    /**
     * @throws HttpClientException
     */
    public function testGetThrowsOnMissingTimestamp(): void
    {
        $client = $this->createClient([
            'result' => ApiResult::Success->value,
            'publicationData' => [
                'type' => PublicationType::Text->value,
                'id' => 123,
                'versionTimeString' => '2026-01-20 15:23:20.123456',
                'title' => 'Test',
                'description' => 'Test',
                'text' => 'Test'
            ]
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('no timestamp');
        $client->get(123);
    }

    /**
     * @throws HttpClientException
     */
    public function testListThrowsOnPublicationNotAnArray(): void
    {
        $client = $this->createClient([
            'result' => ApiResult::Success->value,
            'timeStamp' => time(),
            'publications' => [
                'not an array'
            ]
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('Server response is invalid');
        $client->list();
    }

    /**
     * @throws HttpClientException
     */
    public function testListThrowsOnHydrationError(): void
    {
        $client = $this->createClient([
            'result' => ApiResult::Success->value,
            'timeStamp' => time(),
            'publications' => [
                ['id' => 'not an int'] // This should trigger WrongValueTypeException in fromArray
            ]
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('Server response is invalid');
        $client->list();
    }

    /**
     * @throws InvalidResponseFromServerException
     */
    public function testGetThrowsOnClientException(): void
    {
        $exception = new class('Connection failed') extends Exception implements ClientExceptionInterface {};
        $client = $this->createClient(null, $exception);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Connection failed');
        $client->get(123);
    }

    /**
     * @throws HttpClientException
     */
    public function testGetThrowsOnHydrationError(): void
    {
        $client = $this->createClient([
            'result' => ApiResult::Success->value,
            'timeStamp' => time(),
            'publicationData' => [
                'type' => PublicationType::Text->value,
                'id' => 'not an int' // This should trigger WrongValueTypeException
            ]
        ]);

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('Server response is invalid');
        $client->get(123);
    }

    /**
     * @throws HttpClientException
     */
    public function testThrowsOnJsonNotArray(): void
    {
        $client = $this->createClient('"just a string"');

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('expected JSON object or array');
        $client->list();
    }

    /**
     * @throws HttpClientException
     */
    public function testThrowsOnNonStringKeys(): void
    {
        // JSON cannot have non-string keys, but json_decode with true might produce them in some edge cases?
        // Actually, parseAndValidateResponse uses json_decode which always returns string keys for objects.
        // But if we pass a JSON that is just an array of values, it has numeric keys.
        $client = $this->createClient('[1, 2, 3]');

        $this->expectException(InvalidResponseFromServerException::class);
        $this->expectExceptionMessage('expected JSON object with string keys');
        $client->list();
    }

    public function testListThrowsOn404(): void
    {
        $client = $this->createClient(null, null, 404);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Publication not found');
        $client->list();
    }

    public function testGetThrowsOn404(): void
    {
        $client = $this->createClient(null, null, 404);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Publication not found');
        $client->get(123);
    }

    public function testListThrowsOn500(): void
    {
        $client = $this->createClient(null, null, 500);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Http client error');
        $client->list();
    }

    public function testGetThrowsOn500(): void
    {
        $client = $this->createClient(null, null, 500);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Http client error');
        $client->get(123);
    }
}
