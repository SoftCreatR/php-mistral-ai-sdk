<?php

/*
 * Copyright (c) 2024-present, Sascha Greuel and Contributors
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

namespace SoftCreatR\MistralAI\Tests;

use Exception;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReflectionException;
use SoftCreatR\MistralAI\Exception\MistralAIException;
use SoftCreatR\MistralAI\Http\StreamingClientInterface;
use SoftCreatR\MistralAI\MistralAI;
use Throwable;

/**
 * @covers \SoftCreatR\MistralAI\Exception\MistralAIException
 * @covers \SoftCreatR\MistralAI\MistralAI
 * @covers \SoftCreatR\MistralAI\MistralAIURLBuilder
 */
final class MistralAITest extends TestCase
{
    /**
     * The MistralAI instance used for testing.
     */
    private MistralAI $mistralAI;

    /**
     * The mocked HTTP client used for simulating API responses.
     */
    private ClientInterface&Stub $mockedClient;

    /**
     * API key for the Mistral AI API.
     */
    private string $apiKey = 'sk-...';

    /**
     * Custom origin for the Mistral AI API, if needed.
     */
    private string $origin = 'example.com';

    /**
     * Sets up the test environment by creating an MistralAI instance and
     * a mocked HTTP client, then assigns the mocked client to the MistralAI instance.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $psr17Factory = new HttpFactory();
        $this->mockedClient = $this->createStub(ClientInterface::class);

        $this->mistralAI = new MistralAI(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $this->mockedClient,
            $this->apiKey,
            $this->origin,
        );
    }


    /**
     * Tests that an InvalidArgumentException is thrown when the first argument is not an array.
     *
     * @throws MistralAIException
     * @throws Throwable
     */
    public function testInvalidFirstArgumentInCall(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First argument must be an array of parameters.');

        $this->mistralAI->__call('createChatCompletion', ['invalid_argument']);
    }

    /**
     * Tests that the createMultipartStream method is called and the boundary is generated.
     *
     * @throws Exception
     */
    public function testUploadFileCreatesMultipartStream(): void
    {
        $filePath = __DIR__ . '/fixtures/dummyFile.jsonl';
        \file_put_contents($filePath, 'Dummy content');

        $this->sendRequestMock(function (RequestInterface $request) {
            $body = (string) $request->getBody();
            $this->assertStringContainsString('multipart/form-data', $request->getHeaderLine('Content-Type'));
            $this->assertStringContainsString('Dummy content', $body);

            return new Response(200, [], '{"success": true}');
        });

        // Pass parameters as $opts, not $parameters
        $response = $this->mistralAI->uploadFile([], [
            'file' => $filePath,
            'purpose' => 'fine-tune',
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        \unlink($filePath);
    }

    /**
     * Tests that an MistralAIException is thrown when the API returns an error response.
     */
    public function testCallAPIHandlesErrorResponse(): void
    {
        $this->sendRequestMock(static function () {
            return new Response(400, [], 'Bad Request');
        });

        $this->expectException(MistralAIException::class);
        $this->expectExceptionMessage('Bad Request');

        // Pass options as the second argument
        $this->mistralAI->createChatCompletion([], [
            'model' => 'mistral-small-latest',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Test message',
                ],
            ],
        ]);
    }

    /**
     * Tests that an MistralAIException is thrown when the HTTP client throws a ClientExceptionInterface.
     */
    public function testCallAPICatchesClientException(): void
    {
        $this->sendRequestMock(
            static fn() => throw new class ('Client error', 0) extends Exception implements ClientExceptionInterface {},
        );

        $this->expectException(MistralAIException::class);
        $this->expectExceptionMessage('Client error');

        // Pass options as the second argument
        $this->mistralAI->createChatCompletion([], [
            'model' => 'mistral-small-latest',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Test message',
                ],
            ],
        ]);
    }

    /**
     * Tests that handleStreamingResponse throws an MistralAIException when the response status code is >= 400.
     */
    public function testHandleStreamingResponseHandlesErrorResponse(): void
    {
        $this->sendRequestMock(static function () {
            return new Response(400, [], 'Bad Request');
        });

        $this->expectException(MistralAIException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->mistralAI->createChatCompletion(
            [],
            [
                'model' => 'mistral-small-latest',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test message',
                    ],
                ],
                'stream' => true,
            ],
            static function () {
                // Streaming callback
            },
        );
    }

    /**
     * Tests that handleStreamingResponse continues when data is an empty string.
     */
    public function testHandleStreamingResponseContinuesOnEmptyData(): void
    {
        $fakeResponseContent = "\n"; // Empty data
        $stream = \fopen('php://temp', 'rb+');
        \fwrite($stream, $fakeResponseContent);
        \rewind($stream);

        $fakeResponse = new Response(200, [], $stream);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        $this->mistralAI->createChatCompletion(
            [],
            [
                'model' => 'mistral-small-latest',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test message',
                    ],
                ],
                'stream' => true,
            ],
            fn() => $this->fail('Streaming callback should not be called on empty data.'),
        );

        $this->addToAssertionCount(1);
    }

    /**
     * Tests that handleStreamingResponse throws an MistralAIException when JSON decoding fails.
     */
    public function testHandleStreamingResponseJsonException(): void
    {
        $fakeResponseContent = "data: invalid_json\n";
        $stream = \fopen('php://temp', 'rb+');
        \fwrite($stream, $fakeResponseContent);
        \rewind($stream);

        $fakeResponse = new Response(200, [], $stream);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        $this->expectException(MistralAIException::class);
        $this->expectExceptionMessageMatches('/JSON decode error:/');

        $this->mistralAI->createChatCompletion(
            [],
            [
                'model' => 'mistral-small-latest',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test message',
                    ],
                ],
                'stream' => true,
            ],
            static function ($data) {
                // Streaming callback
            },
        );
    }

    /**
     * Tests that handleStreamingResponse catches ClientExceptionInterface exceptions.
     */
    public function testHandleStreamingResponseCatchesClientException(): void
    {
        $this->sendRequestMock(
            static fn() => throw new class ('Client error in streaming', 0) extends Exception implements ClientExceptionInterface {},
        );

        $this->expectException(MistralAIException::class);
        $this->expectExceptionMessage('Client error in streaming');

        $this->mistralAI->createChatCompletion(
            [],
            [
                'model' => 'mistral-small-latest',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test message',
                    ],
                ],
                'stream' => true,
            ],
            static function () {
                // Streaming callback
            },
        );
    }

    /**
     * Tests that generateMultipartBoundary generates a boundary string.
     *
     * @throws ReflectionException
     */
    public function testGenerateMultipartBoundary(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->mistralAI, 'generateMultipartBoundary');
        $boundary = $reflectionMethod->invoke($this->mistralAI);

        $this->assertMatchesRegularExpression('/^----MistralAI[0-9a-f]{32}$/', $boundary);
    }

    /**
     * Tests that createHeaders sets the correct Content-Type for multipart requests.
     *
     * @throws ReflectionException
     */
    public function testCreateHeadersForMultipartRequest(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->mistralAI, 'createHeaders');
        $boundary = 'testBoundary';

        $headers = $reflectionMethod->invoke($this->mistralAI, true, $boundary);

        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals("multipart/form-data; boundary={$boundary}", $headers['Content-Type']);
    }

    /**
     * Tests that createJsonBody throws an MistralAIException when JSON encoding fails.
     *
     * @throws ReflectionException
     */
    public function testCreateJsonBodyJsonException(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->mistralAI, 'createJsonBody');

        $this->expectException(MistralAIException::class);
        $this->expectExceptionMessageMatches('/^JSON encode error:/');

        $invalidValue = \tmpfile(); // Cannot be JSON encoded
        $params = ['invalid' => $invalidValue];

        $reflectionMethod->invoke($this->mistralAI, $params);
    }

    /**
     * Tests that createMultipartStream creates a valid multipart stream.
     *
     * @throws ReflectionException
     */
    public function testCreateMultipartStream(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->mistralAI, 'createMultipartStream');
        $boundary = 'testBoundary';
        $filePath = __DIR__ . '/fixtures/dummyFile.jsonl';
        \file_put_contents($filePath, 'Dummy content');

        $params = [
            'file' => $filePath,
            'purpose' => 'fine-tune',
        ];

        $multipartStream = (string) $reflectionMethod->invoke($this->mistralAI, $params, $boundary);

        $this->assertStringContainsString("--{$boundary}\r\n", $multipartStream);
        $this->assertStringContainsString('Content-Disposition: form-data; name="file"; filename', $multipartStream);
        $this->assertStringContainsString('Dummy content', $multipartStream);

        \unlink($filePath);
    }

    /**
     * Tests that createMultipartStream writes upload-part data as raw bytes.
     *
     * @throws ReflectionException
     */
    public function testCreateMultipartStreamWithData(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->mistralAI, 'createMultipartStream');
        $boundary = 'testBoundary';
        $filePath = __DIR__ . '/fixtures/dummyFile.bin';
        \file_put_contents($filePath, 'Binary content');

        $params = [
            'data' => $filePath,
            'purpose' => 'fine-tune',
        ];

        $multipartStream = (string) $reflectionMethod->invoke($this->mistralAI, $params, $boundary, ['data']);

        $this->assertStringContainsString("--{$boundary}\r\n", $multipartStream);
        $this->assertStringContainsString('Content-Disposition: form-data; name="data"; filename', $multipartStream);
        $this->assertStringContainsString('Binary content', $multipartStream);
        $this->assertStringNotContainsString(\base64_encode('Binary content'), $multipartStream);

        \unlink($filePath);
    }

    /**
     * Tests that the createChatCompletion method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testCreateChatCompletion(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->createChatCompletion([], [
                'model' => 'mistral-small-latest',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant.',
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Hello!',
                    ],
                ],
            ]),
            'chatCompletion.json',
        );
    }

    /**
     * The README has always documented body-first calls, so 4.0 must send this body.
     */
    public function testCreateChatCompletionSupportsBodyFirstCall(): void
    {
        $this->sendRequestMock(function (RequestInterface $request) {
            $this->assertSame('POST', $request->getMethod());
            $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
            $this->assertSame(
                ['model' => 'mistral-small-latest', 'messages' => [['role' => 'user', 'content' => 'Hello']]],
                \json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR),
            );

            return new Response(200, ['Content-Type' => 'application/json'], '{}');
        });

        $this->mistralAI->createChatCompletion([
            'model' => 'mistral-small-latest',
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ]);
    }

    public function testCallbackDoesNotDiscardANonStreamingResponse(): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}');
        $this->sendRequestMock(static fn() => $response);

        $actual = $this->mistralAI->createChatCompletion(
            ['model' => 'mistral-small-latest', 'messages' => []],
            fn() => $this->fail('A callback must not run for a non-streaming response.'),
        );

        $this->assertSame($response, $actual);
    }

    /**
     * Tests that the createChatCompletion method handles streaming API calls correctly.
     *
     * @throws Exception
     */
    public function testCreateChatCompletionWithStreaming(): void
    {
        $output = '';

        $streamCallback = static function ($data) use (&$output) {
            if (isset($data['choices'][0]['delta']['content'])) {
                $output .= $data['choices'][0]['delta']['content'];
            }
        };

        $this->testApiCallWithStreaming(
            fn($streamCallback) => $this->mistralAI->createChatCompletion(
                [],
                [
                    'model' => 'mistral-small-latest',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Tell me a story about a brave knight.',
                        ],
                    ],
                    'stream' => true,
                ],
                $streamCallback,
            ),
            $streamCallback,
        );

        $expectedOutput = 'Hello';
        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testStreamingRequestUsesStreamingTransportWhenAvailable(): void
    {
        $psr17Factory = new HttpFactory();
        $client = $this->createMock(StreamingClientInterface::class);
        $response = new Response(
            200,
            ['Content-Type' => 'text/event-stream'],
            "data: {\"value\":\"streamed\"}\n\n",
        );
        $client->expects($this->once())
            ->method('sendStreamingRequest')
            ->willReturn($response);
        $client->expects($this->never())
            ->method('sendRequest');

        $mistralAI = new MistralAI(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $client,
            $this->apiKey,
            $this->origin,
        );
        $events = [];

        $actual = $mistralAI->createChatCompletion(
            ['model' => 'mistral-small-latest', 'messages' => [], 'stream' => true],
            static function (array $event) use (&$events): void {
                $events[] = $event;
            },
        );

        $this->assertSame($response, $actual);
        $this->assertSame([['value' => 'streamed']], $events);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testEndpointMetadataCanSelectTheStreamingTransport(): void
    {
        $psr17Factory = new HttpFactory();
        $client = $this->createMock(StreamingClientInterface::class);
        $response = new Response(
            200,
            ['Content-Type' => 'text/event-stream'],
            "data: {\"type\":\"agent.session.updated\"}\n\n",
        );
        $client->expects($this->once())
            ->method('sendStreamingRequest')
            ->willReturn($response);
        $client->expects($this->never())
            ->method('sendRequest');

        $mistralAI = new MistralAI(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $client,
            $this->apiKey,
            $this->origin,
        );
        $events = [];

        $actual = $mistralAI->streamWorkflowExecution(
            ['execution_id' => 'exec_abc123'],
            static function (array $event) use (&$events): void {
                $events[] = $event;
            },
        );

        $this->assertSame($response, $actual);
        $this->assertSame([['type' => 'agent.session.updated']], $events);
    }

    /**
     * Tests that the listModels method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testListModels(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->listModels(),
            'listModels.json',
        );
    }

    /**
     * Tests that the retrieveModel method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testRetrieveModel(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->retrieveModel(['model_id' => 'mistral-small-latest']),
            'retrieveModel.json',
        );
    }

    /**
     * Tests that the uploadFile method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testUploadFile(): void
    {
        $filePath = __DIR__ . '/fixtures/dummyFile.jsonl';
        \file_put_contents($filePath, '{"prompt": "Hello", "completion": "World"}');

        $this->testApiCall(
            fn() => $this->mistralAI->uploadFile([], [
                'file' => $filePath,
                'purpose' => 'fine-tune',
            ]),
            'uploadFile.json',
        );

        \unlink($filePath);
    }

    /**
     * @throws ReflectionException
     */
    public function testExtractCallArgumentsWithCallableAsSecondArgument(): void
    {
        $reflection = TestHelper::getPrivateMethod($this->mistralAI, 'extractCallArguments');
        $callback = static fn() => 'i-am-called';

        // Pass [ parameters, callback ]
        [$parameters, $opts, $streamCallback] = $reflection->invoke(
            $this->mistralAI,
            [ ['foo' => 'bar'], $callback ],
        );

        $this->assertSame(['foo' => 'bar'], $parameters);
        $this->assertSame([], $opts);
        $this->assertSame($callback, $streamCallback);
    }

    /**
     * @throws MistralAIException
     * @throws Throwable
     */
    public function testRejectsMoreThanThreeEndpointArguments(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Endpoint calls accept at most three arguments.');

        $this->mistralAI->__call('listModels', [[], [], static fn() => null, []]);
    }

    /**
     * @throws MistralAIException
     * @throws Throwable
     */
    public function testRejectsANonArrayNonCallableSecondArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Second argument must be an array or callable.');

        $this->mistralAI->__call('listModels', [[], 'invalid']);
    }

    /**
     * @throws MistralAIException
     * @throws Throwable
     */
    public function testRejectsAThirdArgumentWithoutASecondArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Third argument must be a stream callback.');

        $this->mistralAI->__call('listModels', [[], static fn() => null, static fn() => null]);
    }

    /**
     * @throws Throwable
     */
    public function testSplitsCombinedPathParametersFromTheRequestBody(): void
    {
        $this->sendRequestMock(function (RequestInterface $request) {
            $this->assertSame('/v1/conversations/conv_123', $request->getUri()->getPath());
            $this->assertSame(
                ['metadata' => ['topic' => 'coverage']],
                \json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR),
            );

            return new Response(200, [], '{}');
        });

        $this->mistralAI->appendConversation([
            'conversation_id' => 'conv_123',
            'metadata' => ['topic' => 'coverage'],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function testRejectsInvalidCustomHeadersInTheFirstArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('customHeaders must be an array.');

        $this->mistralAI->listModels(['customHeaders' => 'invalid']);
    }

    /**
     * @throws Throwable
     */
    public function testRejectsInvalidCustomHeadersInTheSecondArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('customHeaders must be an array.');

        $this->mistralAI->createChatCompletion([], ['customHeaders' => 'invalid']);
    }

    /**
     * @throws Throwable
     */
    public function testMergesCustomHeadersFromBothArguments(): void
    {
        $this->sendRequestMock(function (RequestInterface $request) {
            $this->assertSame('first', $request->getHeaderLine('X-First'));
            $this->assertSame('second', $request->getHeaderLine('X-Second'));
            $this->assertSame('second', $request->getHeaderLine('X-Shared'));
            $this->assertArrayNotHasKey(
                'customHeaders',
                \json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR),
            );

            return new Response(200, [], '{}');
        });

        $this->mistralAI->createChatCompletion(
            ['customHeaders' => ['X-First' => 'first', 'X-Shared' => 'first']],
            [
                'model' => 'mistral-small-latest',
                'messages' => [],
                'customHeaders' => ['X-Second' => 'second', 'X-Shared' => 'second'],
            ],
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testInfersLegacyEndpointBodyTypes(): void
    {
        $reflection = TestHelper::getPrivateMethod($this->mistralAI, 'inferBodyType');

        $this->assertSame('none', $reflection->invoke($this->mistralAI, 'GET', '/models'));
        $this->assertSame('multipart', $reflection->invoke($this->mistralAI, 'POST', '/audio/transcriptions'));
        $this->assertSame('json', $reflection->invoke($this->mistralAI, 'POST', '/responses'));
    }

    /**
     * @throws ReflectionException
     */
    public function testCreatesLegacyJsonHeadersFromFalseMultipartFlag(): void
    {
        $reflection = TestHelper::getPrivateMethod($this->mistralAI, 'createHeaders');
        $headers = $reflection->invoke($this->mistralAI, false);

        $this->assertIsArray($headers);
        $this->assertSame('application/json', $headers['Content-Type']);
    }

    /**
     * Ensure that GET requests with parameters and options
     * get merged into the URI query string.
     */
    public function testListModelsAddsQueryParametersToUri(): void
    {
        $this->sendRequestMock(function (RequestInterface $request) {
            $query = $request->getUri()->getQuery();

            $this->assertStringContainsString('foo=bar', $query);
            $this->assertStringContainsString('baz=qux', $query);

            return new Response(200, [], '{"success":true}');
        });

        $response = $this->mistralAI->listModels(
            ['foo' => 'bar'],
            ['baz' => 'qux'],
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Custom headers on GET requests must not be serialized as query parameters.
     */
    public function testGetRequestExtractsCustomHeaders(): void
    {
        $this->sendRequestMock(function (RequestInterface $request) {
            $this->assertSame('test-request-id', $request->getHeaderLine('X-Client-Request-Id'));
            $this->assertStringNotContainsString('customHeaders', $request->getUri()->getQuery());

            return new Response(200, [], '{}');
        });

        $this->mistralAI->listModels([
            'limit' => 10,
            'customHeaders' => ['X-Client-Request-Id' => 'test-request-id'],
        ]);
    }

    /**
     * Mocks an API call using a callable and a response file.
     *
     * Mocks the HTTP client to return a predefined response loaded from a file,
     * and checks if the status code and response body match the expected values.
     *
     * @param callable $apiCall      The API call to test.
     * @param string   $responseFile The path to the file containing the expected response.
     *
     * @throws Exception
     */
    private function testApiCall(callable $apiCall, string $responseFile): void
    {
        $fakeResponseBody = TestHelper::loadResponseFromFile($responseFile);
        $fakeResponse = new Response(200, [], $fakeResponseBody);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        try {
            $response = $apiCall();
        } catch (Exception $e) {
            $this->fail('Exception occurred during API call: ' . $e->getMessage());
        }

        self::assertNotNull($response, 'Response should not be null.');
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($fakeResponseBody, (string) $response->getBody());
    }

    /**
     * Mocks an API call with streaming support using a callable and a response file.
     *
     * Mocks the HTTP client to return a predefined streaming response loaded from a file,
     * and utilizes the provided stream callback to process the response.
     *
     * @param callable $apiCall       The API call to test.
     * @param callable $streamCallback The callback function to handle streaming data.
     *
     * @throws Exception
     */
    private function testApiCallWithStreaming(callable $apiCall, callable $streamCallback): void
    {
        $fakeResponseContent = TestHelper::loadResponseFromFile('chatCompletionStreaming.txt');
        $fakeChunks = \explode("\n", \trim($fakeResponseContent));
        $stream = \fopen('php://temp', 'rb+');

        foreach ($fakeChunks as $chunk) {
            \fwrite($stream, $chunk . "\n");
        }
        \rewind($stream);

        $fakeResponse = new Response(200, [], $stream);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        try {
            $apiCall($streamCallback);
        } catch (Exception $e) {
            $this->fail('Exception occurred during streaming: ' . $e->getMessage());
        }
    }

    /**
     * Sets up a mock for the sendRequest method of the mocked client.
     *
     * @param callable $responseCallback A callable that returns a response or throws an exception.
     */
    private function sendRequestMock(callable $responseCallback): void
    {
        $this->mockedClient
            ->method('sendRequest')
            ->willReturnCallback($responseCallback);
    }
}
