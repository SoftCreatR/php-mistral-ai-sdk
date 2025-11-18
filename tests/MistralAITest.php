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
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReflectionException;
use SoftCreatR\MistralAI\Exception\MistralAIException;
use SoftCreatR\MistralAI\MistralAI;
use SoftCreatR\MistralAI\MistralAIURLBuilder;

/**
 * @covers \SoftCreatR\MistralAI\Exception\MistralAIException
 * @covers \SoftCreatR\MistralAI\MistralAI
 * @covers \SoftCreatR\MistralAI\MistralAIURLBuilder
 */
class MistralAITest extends TestCase
{
    /**
     * The MistralAI instance used for testing.
     */
    private MistralAI $mistralAI;

    /**
     * The mocked HTTP client used for simulating API responses.
     */
    private ClientInterface $mockedClient;

    /**
     * API key for the MistralAI API.
     */
    private string $apiKey = 'jUsTaRaNdOmStRiNg';

    /**
     * Custom origin for the MistralAI API, if needed.
     */
    private string $origin = 'example.com';

    /**
     * Sets up the test environment by creating a MistralAI instance and
     * a mocked HTTP client, then assigns the mocked client to the MistralAI instance.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $psr17Factory = new HttpFactory();
        $this->mockedClient = $this->createMock(ClientInterface::class);

        $this->mistralAI = new MistralAI(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $this->mockedClient,
            $this->apiKey,
            $this->origin
        );
    }

    /**
     * Tests that an InvalidArgumentException is thrown when the first argument is not an array.
     */
    public function testInvalidFirstArgumentInCall(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First argument must be an array of parameters.');

        $this->mistralAI->createChatCompletion('invalid_argument');
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

        $this->sendRequestMock(function (RequestInterface $request) use ($filePath) {
            $body = (string)$request->getBody();
            $this->assertStringContainsString('multipart/form-data', $request->getHeaderLine('Content-Type'));
            $this->assertStringContainsString('Dummy content', $body);
            $this->assertStringContainsString(\basename($filePath), $body);

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
     * Tests that a MistralAIException is thrown when the API returns an error response.
     */
    public function testCallAPIHandlesErrorResponse(): void
    {
        $this->sendRequestMock(static function () {
            return new Response(400, [], 'Bad Request');
        });

        $this->expectException(MistralAIException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->mistralAI->createChatCompletion([
            'model' => 'mistral-tiny',
            'messages' => [
                ['role' => 'user', 'content' => 'Test message'],
            ],
        ]);
    }

    /**
     * Tests that a MistralAIException is thrown when the HTTP client throws a ClientExceptionInterface.
     */
    public function testCallAPICatchesClientException(): void
    {
        $this->sendRequestMock(static function () {
            throw new class ('Client error', 0) extends Exception implements ClientExceptionInterface {};
        });

        $this->expectException(MistralAIException::class);
        $this->expectExceptionMessage('Client error');

        $this->mistralAI->createChatCompletion([
            'model' => 'mistral-tiny',
            'messages' => [
                ['role' => 'user', 'content' => 'Test message'],
            ],
        ]);
    }

    /**
     * Tests that handleStreamingResponse throws a MistralAIException when the response status code is >= 400.
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
                'model' => 'mistral-tiny',
                'messages' => [
                    ['role' => 'user', 'content' => 'Test message'],
                ],
                'stream' => true,
            ],
            static function (): void {}
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
                'model' => 'mistral-tiny',
                'messages' => [
                    ['role' => 'user', 'content' => 'Test message'],
                ],
                'stream' => true,
            ],
            function () {
                $this->fail('Streaming callback should not be called on empty data.');
            }
        );

        $this->assertTrue(true); // If no exception is thrown, test passes
    }

    /**
     * Tests that handleStreamingResponse throws a MistralAIException when JSON decoding fails.
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
        $this->expectExceptionMessage('JSON decode error: Syntax error');

        // Correctly pass parameters and options
        $this->mistralAI->createChatCompletion(
            [],
            [
                'model' => 'mistral-tiny',
                'messages' => [
                    ['role' => 'user', 'content' => 'Test message'],
                ],
                'stream' => true,
            ],
            static function ($data) {
                // Streaming callback
            }
        );
    }

    /**
     * Tests that handleStreamingResponse catches ClientExceptionInterface exceptions.
     */
    public function testHandleStreamingResponseCatchesClientException(): void
    {
        $this->sendRequestMock(static function () {
            throw new class ('Client error in streaming', 0) extends Exception implements ClientExceptionInterface {};
        });

        $this->expectException(MistralAIException::class);
        $this->expectExceptionMessage('Client error in streaming');

        $this->mistralAI->createChatCompletion(
            [],
            [
                'model' => 'mistral-tiny',
                'messages' => [
                    ['role' => 'user', 'content' => 'Test message'],
                ],
                'stream' => true,
            ],
            static function (): void {}
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

        $headers = $reflectionMethod->invoke($this->mistralAI, true, $boundary, true, []);

        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals("multipart/form-data; boundary={$boundary}", $headers['Content-Type']);
        $this->assertEquals('application/json', $headers['Accept']);
    }

    /**
     * Tests that createHeaders sets the Accept header for streaming requests.
     *
     * @throws ReflectionException
     */
    public function testCreateHeadersForStreamingRequest(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->mistralAI, 'createHeaders');

        $headers = $reflectionMethod->invoke($this->mistralAI, false, null, false, ['stream' => true]);

        $this->assertSame('text/event-stream', $headers['Accept']);
        $this->assertArrayNotHasKey('Content-Type', $headers, 'Content-Type should not be set when there is no body.');
    }

    /**
     * Ensures GET requests serialize query parameters instead of sending a JSON body.
     */
    public function testGetRequestAppendsQueryParameters(): void
    {
        $this->sendRequestMock(static function (RequestInterface $request) {
            self::assertSame('limit=5&order=desc', $request->getUri()->getQuery());
            self::assertSame('', (string)$request->getBody());

            return new Response(200, [], '{}');
        });

        $this->mistralAI->listModels([], ['limit' => 5, 'order' => 'desc']);
    }

    /**
     * Ensures body-capable methods still allow query parameters.
     */
    public function testPatchRequestSupportsQueryParameters(): void
    {
        $this->sendRequestMock(static function (RequestInterface $request) {
            self::assertSame('version=2.0.0', $request->getUri()->getQuery());
            self::assertSame('{"notes":"Promote"}', (string)$request->getBody());

            return new Response(200, [], '{}');
        });

        $this->mistralAI->updateAgentVersion(
            ['agent_id' => 'agent_123'],
            [
                '_query' => ['version' => '2.0.0'],
                'notes' => 'Promote',
            ]
        );
    }

    /**
     * Tests that createJsonBody throws a MistralAIException when JSON encoding fails.
     *
     * @throws ReflectionException
     */
    public function testCreateJsonBodyJsonException(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->mistralAI, 'createJsonBody');

        $this->expectException(MistralAIException::class);

        // Since exception messages can vary, you can omit the exact message or adjust it to match.
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

        $multipartStream = $reflectionMethod->invoke($this->mistralAI, $params, $boundary);

        $this->assertStringContainsString("--{$boundary}\r\n", $multipartStream);
        $this->assertStringContainsString('Content-Disposition: form-data; name="file"; filename="dummyFile.jsonl"', $multipartStream);
        $this->assertStringContainsString('Dummy content', $multipartStream);

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
            fn() => $this->mistralAI->createChatCompletion([
                'model' => 'mistral-tiny',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'What is the best French cheese?',
                    ],
                ],
            ]),
            'chatCompletion.json'
        );
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
                $streamCallback
            ),
            'chatCompletionStreaming.json',
            $streamCallback
        );

        $expectedOutput = 'Once upon a time, in a land far away, there lived a brave knight named Sir Alaric.';
        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * Ensures the audio transcription streaming helper always enables streaming responses.
     */
    public function testCreateAudioTranscriptionStream(): void
    {
        $fakeResponseContent = "data: {\"text\":\"hi\"}\n\n" . "data: [DONE]\n";
        $stream = \fopen('php://temp', 'rb+');
        \fwrite($stream, $fakeResponseContent);
        \rewind($stream);

        $fakeResponse = new Response(200, [], $stream);

        $this->sendRequestMock(static function (RequestInterface $request) use ($fakeResponse) {
            self::assertSame('text/event-stream', $request->getHeaderLine('Accept'));
            self::assertStringContainsString('"stream":true', (string)$request->getBody());

            return $fakeResponse;
        });

        $captured = '';

        $this->mistralAI->createAudioTranscriptionStream([], [
            'model' => 'mistral-scribe',
            'audio_url' => 'https://example.com/audio.wav',
        ], static function (array $data) use (&$captured): void {
            $captured .= $data['text'] ?? '';
        });

        $this->assertSame('hi', $captured);
    }

    /**
     * Tests that the createEmbedding method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testCreateEmbedding(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->createEmbedding([
                'model' => 'mistral-embed',
                'input' => ['Hello world', 'Test embedding'],
            ]),
            'createEmbedding.json'
        );
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
            'listModels.json'
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
            fn() => $this->mistralAI->retrieveModel(['model_id' => 'model_12345']),
            'retrieveModel.json'
        );
    }

    /**
     * Tests that the deleteModel method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testDeleteModel(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->deleteModel(['model_id' => 'model_12345']),
            'deleteModel.json'
        );
    }

    /**
     * Tests that the updateFineTunedModel method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testUpdateFineTunedModel(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->updateFineTunedModel([
                'model_id' => 'model_12345',
                'new_parameter' => 'value',
            ]),
            'updateFineTunedModel.json'
        );
    }

    /**
     * Tests that the archiveModel method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testArchiveModel(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->archiveModel(['model_id' => 'model_12345']),
            'archiveModel.json'
        );
    }

    /**
     * Tests that the unarchiveModel method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testUnarchiveModel(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->unarchiveModel(['model_id' => 'model_12345']),
            'unarchiveModel.json'
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
        \file_put_contents($filePath, 'Dummy content');

        $this->testApiCall(
            fn() => $this->mistralAI->uploadFile([
                'file' => $filePath,
                'purpose' => 'fine-tune',
            ]),
            'uploadFile.json'
        );

        \unlink($filePath);
    }

    /**
     * Tests that the listFiles method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testListFiles(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->listFiles(),
            'listFiles.json'
        );
    }

    /**
     * Tests that the retrieveFile method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testRetrieveFile(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->retrieveFile(['file_id' => 'file_12345']),
            'retrieveFile.json'
        );
    }

    /**
     * Tests that the deleteFile method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testDeleteFile(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->deleteFile(['file_id' => 'file_12345']),
            'deleteFile.json'
        );
    }

    /**
     * Tests that the listFineTuningJobs method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testListFineTuningJobs(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->listFineTuningJobs(),
            'listFineTuningJobs.json'
        );
    }

    /**
     * Tests that the retrieveFineTuningJob method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testRetrieveFineTuningJob(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->retrieveFineTuningJob(['job_id' => 'job_12345']),
            'retrieveFineTuningJob.json'
        );
    }

    /**
     * Tests that the cancelFineTuningJob method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testCancelFineTuningJob(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->cancelFineTuningJob(['job_id' => 'job_12345']),
            'cancelFineTuningJob.json'
        );
    }

    /**
     * Tests that the startFineTuningJob method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testStartFineTuningJob(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->startFineTuningJob(['job_id' => 'job_12345']),
            'startFineTuningJob.json'
        );
    }

    /**
     * Tests that the createFineTuningJob method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testCreateFineTuningJob(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->createFineTuningJob([
                'training_file' => 'file_12345',
                'model' => 'mistral-tiny',
            ]),
            'createFineTuningJob.json'
        );
    }

    /**
     * Tests that the createFimCompletion method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testCreateFimCompletion(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->createFimCompletion([
                'model' => 'mistral-fim',
                'prompt' => 'Once upon a time, in a land far away, there lived a brave knight named Sir Alaric.',
                'insert_text' => 'Sir Alaric was known for his',
            ]),
            'createFimCompletion.json'
        );
    }

    /**
     * Tests that the createAgentsCompletion method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testCreateAgentsCompletion(): void
    {
        $this->testApiCall(
            fn() => $this->mistralAI->createAgentsCompletion([
                'model' => 'mistral-agent',
                'tasks' => [
                    ['task' => 'Analyze sentiment', 'input' => 'I love programming!'],
                    ['task' => 'Translate text', 'input' => 'Hello, how are you?', 'target_language' => 'es'],
                ],
            ]),
            'createAgentsCompletion.json'
        );
    }

    /**
     * Tests the 'extractCallArguments' method with various input scenarios.
     *
     * Ensures that the method correctly extracts parameters, options, and the stream callback from the provided arguments.
     *
     * @throws ReflectionException
     */
    public function testExtractCallArguments(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->mistralAI, 'extractCallArguments');
        $retrieveEndpoint = MistralAIURLBuilder::getEndpoint('retrieveModel');
        $chatEndpoint = MistralAIURLBuilder::getEndpoint('createChatCompletion');

        $result = $reflectionMethod->invoke($this->mistralAI, $retrieveEndpoint, [['model_id' => 'model_123']]);
        $this->assertSame(['model_id' => 'model_123'], $result[0]);
        $this->assertSame([], $result[1]);
        $this->assertNull($result[2]);

        $result = $reflectionMethod->invoke($this->mistralAI, $retrieveEndpoint, [['model_id' => 'model_456'], ['description' => 'test']]);
        $this->assertSame(['model_id' => 'model_456'], $result[0]);
        $this->assertSame(['description' => 'test'], $result[1]);

        $streamCallback = static function (): void {};
        $result = $reflectionMethod->invoke($this->mistralAI, $chatEndpoint, [[
            'model' => 'mistral-tiny',
            'messages' => [['role' => 'user', 'content' => 'Hi']],
        ], $streamCallback]);
        $this->assertSame([], $result[0]);
        $this->assertEquals('mistral-tiny', $result[1]['model']);
        $this->assertSame($streamCallback, $result[2]);

        $result = $reflectionMethod->invoke($this->mistralAI, $chatEndpoint, [[
            'model' => 'mistral-medium',
        ], ['temperature' => 0.2]]);
        $this->assertSame(['model' => 'mistral-medium'], $result[0]);
        $this->assertSame(['temperature' => 0.2], $result[1]);
    }

    /**
     * Tests that callAPI handles JSON encoding errors correctly.
     *
     * Ensures that when JSON encoding fails due to an invalid value,
     * the method catches the JsonException and sets the request body to an empty string.
     */
    public function testCallAPIJsonEncodingException(): void
    {
        $this->expectException(MistralAIException::class);
        $this->expectExceptionMessageMatches('/JSON encode error/i');

        $invalidValue = \tmpfile(); // Create an invalid value that cannot be JSON encoded

        $this->mistralAI->createChatCompletion([
            'model' => 'mistral-tiny',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $invalidValue,
                ],
            ],
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
        } catch (Exception) {
            $response = null;
        }

        self::assertNotNull($response, 'Response should not be null.');
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($fakeResponseBody, (string)$response->getBody());
    }

    /**
     * Mocks an API call with streaming support using a callable and a response file.
     *
     * Mocks the HTTP client to return a predefined streaming response loaded from a file,
     * and utilizes the provided stream callback to process the response.
     *
     * @param callable $apiCall       The API call to test.
     * @param string   $responseFile  The path to the file containing the expected streaming response.
     * @param callable $streamCallback The callback function to handle streaming data.
     *
     * @throws Exception
     */
    private function testApiCallWithStreaming(callable $apiCall, string $responseFile, callable $streamCallback): void
    {
        $fakeResponseContent = TestHelper::loadResponseFromFile($responseFile);
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
            ->expects(self::once())
            ->method('sendRequest')
            ->willReturnCallback($responseCallback);
    }
}
