<?php

/*
 * Copyright (c) 2024, Sascha Greuel and Contributors
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
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReflectionException;
use SoftCreatR\MistralAI\MistralAI;

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
     * Sets up the test environment by creating an MistralAI instance and
     * a mocked HTTP client, then assigning the mocked client to the MistralAI instance.
     *
     * This method is called before each test method is executed.
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
     * Test that MistralAI::chat method can handle API calls correctly.
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
     * Test the 'extractCallArguments' method with various input scenarios.
     *
     * This test ensures that the 'extractCallArguments' method correctly extracts
     * the parameter and options from the provided arguments array for different cases:
     * - String parameter and options array
     * - Only string parameter
     * - Only options array
     * - Empty array
     *
     * @throws ReflectionException
     */
    public function testExtractCallArguments(): void
    {
        // Invoke the protected method 'extractCallArguments' via reflection
        $reflectionMethod = TestHelper::getPrivateMethod($this->mistralAI, 'extractCallArguments');

        $testCases = [
            [['stringParam', ['key' => 'value']], ['stringParam', ['key' => 'value']]],
            [['stringParam'], ['stringParam', []]],
            [[['key' => 'value']], [null, ['key' => 'value']]],
            [[], [null, []]],
        ];

        foreach ($testCases as $testCase) {
            [$args, $expected] = $testCase;
            $result = $reflectionMethod->invoke($this->mistralAI, $args);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test that MistralAI::callAPI handles JSON encoding errors correctly.
     *
     * This test ensures that when the JSON encoding fails due to an invalid value,
     * the method catches the JsonException and sets the request body to an empty string.
     */
    public function testCallAPIJsonEncodingException(): void
    {
        $this->sendRequestMock(static function (RequestInterface $request) {
            $fakeResponse = new Response(200, [], '');
            // Check if the request body is empty
            self::assertEquals('', (string)$request->getBody());

            return $fakeResponse;
        });

        $invalidValue = \tmpfile(); // create an invalid value that cannot be JSON encoded
        $response = null;

        try {
            $response = $this->mistralAI->createChatCompletion([
                'model' => 'mistral-tiny',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $invalidValue, // pass the invalid value
                    ],
                ],
            ]);
        } catch (Exception $e) {
            // ignore
        }

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('', (string)$response->getBody());
    }

    /**
     * Test an API call using a callable and a response file.
     * This method mocks the HTTP client to return a predefined response loaded from a file,
     * and checks if the status code and the response body match the expected values.
     *
     * @param callable $apiCall The API call to test, wrapped in a callable function.
     * @param string $responseFile The path to the file containing the expected response.
     */
    private function testApiCall(callable $apiCall, string $responseFile): void
    {
        $response = null;
        $fakeResponseBody = TestHelper::loadResponseFromFile($responseFile);
        $fakeResponse = new Response(200, [], $fakeResponseBody);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        try {
            $response = $apiCall();
        } catch (Exception $e) {
            // ignore
        }

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($fakeResponseBody, (string)$response->getBody());
    }

    /**
     * Sets up a mock for the sendRequest method of the mocked client.
     *
     * This helper method is used to reduce code duplication when configuring
     * the sendRequest mock in multiple test cases. It accepts a callable, which
     * will be used as the return value or exception thrown by the sendRequest mock.
     *
     * @param callable $responseCallback A callable that returns a response or throws an exception
     */
    private function sendRequestMock(callable $responseCallback): void
    {
        $this->mockedClient
            ->expects(self::once())
            ->method('sendRequest')
            ->willReturnCallback($responseCallback);
    }
}
