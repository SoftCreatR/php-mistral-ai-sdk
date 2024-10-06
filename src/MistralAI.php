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

namespace SoftCreatR\MistralAI;

use InvalidArgumentException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Random\RandomException;
use SensitiveParameter;
use SoftCreatR\MistralAI\Exception\MistralAIException;

use const JSON_THROW_ON_ERROR;

/**
 * A wrapper for the MistralAI API.
 *
 * @method ResponseInterface|null listModels() Perform a GET request to list all models.
 * @method ResponseInterface|null retrieveModel(array $parameters) Perform a GET request to retrieve a specific model.
 * @method ResponseInterface|null deleteModel(array $parameters) Perform a DELETE request to delete a specific model.
 * @method ResponseInterface|null updateFineTunedModel(array $parameters, array $options = []) Perform a PATCH request to update a fine-tuned model.
 * @method ResponseInterface|null archiveModel(array $parameters) Perform a POST request to archive a specific model.
 * @method ResponseInterface|null unarchiveModel(array $parameters) Perform a DELETE request to unarchive a specific model.
 * @method ResponseInterface|null uploadFile(array $parameters, array $options = []) Perform a POST request to upload a file.
 * @method ResponseInterface|null listFiles() Perform a GET request to list all files.
 * @method ResponseInterface|null retrieveFile(array $parameters) Perform a GET request to retrieve a specific file.
 * @method ResponseInterface|null deleteFile(array $parameters) Perform a DELETE request to delete a specific file.
 * @method ResponseInterface|null listFineTuningJobs() Perform a GET request to list all fine-tuning jobs.
 * @method ResponseInterface|null retrieveFineTuningJob(array $parameters) Perform a GET request to retrieve a specific fine-tuning job.
 * @method ResponseInterface|null cancelFineTuningJob(array $parameters) Perform a POST request to cancel a specific fine-tuning job.
 * @method ResponseInterface|null startFineTuningJob(array $parameters) Perform a POST request to start a specific fine-tuning job.
 * @method ResponseInterface|null createFineTuningJob(array $parameters, array $options = []) Perform a POST request to create a fine-tuning job.
 * @method ResponseInterface|null createChatCompletion(array $parameters, array $options = [], ?\Closure $callback = null) Perform a POST request to create a chat completion.
 * @method ResponseInterface|null createFimCompletion(array $parameters, array $options = [], ?\Closure $callback = null) Perform a POST request to create a FIM completion.
 * @method ResponseInterface|null createAgentsCompletion(array $parameters, array $options = [], ?\Closure $callback = null) Perform a POST request to create an agents completion.
 * @method ResponseInterface|null createEmbedding(array $parameters, array $options = []) Perform a POST request to create an embedding.
 */
class MistralAI
{
    /**
     * Constructs a new instance of the MistralAI client.
     *
     * @param RequestFactoryInterface $requestFactory The PSR-17 request factory.
     * @param StreamFactoryInterface  $streamFactory  The PSR-17 stream factory.
     * @param UriFactoryInterface     $uriFactory     The PSR-17 URI factory.
     * @param ClientInterface         $httpClient     The PSR-18 HTTP client.
     * @param string                  $apiKey         Your MistralAI API key.
     * @param string                  $origin         Custom API origin (hostname).
     * @param string                  $apiVersion     Custom API version.
     */
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UriFactoryInterface $uriFactory,
        private readonly ClientInterface $httpClient,
        #[SensitiveParameter]
        private readonly string $apiKey,
        private readonly string $origin = '',
        private readonly string $apiVersion = ''
    ) {}

    /**
     * Magic method to call the MistralAI API endpoints.
     *
     * @param string $key The endpoint method.
     * @param array $args The arguments for the endpoint method.
     *
     * @return ResponseInterface|null The API response or null if streaming.
     *
     * @throws MistralAIException       If the API returns an error.
     * @throws InvalidArgumentException If the parameters are invalid.
     * @throws RandomException
     */
    public function __call(string $key, array $args): ?ResponseInterface
    {
        $endpoint = MistralAIURLBuilder::getEndpoint($key);
        $httpMethod = $endpoint['method'];

        [$parameters, $opts, $streamCallback] = $this->extractCallArguments($args);

        return $this->callAPI($httpMethod, $key, $parameters, $opts, $streamCallback);
    }

    /**
     * Extracts the arguments from the input array.
     *
     * @param array $args The input arguments.
     *
     * @return array An array containing the extracted parameters, options, and stream callback.
     *
     * @throws InvalidArgumentException If the first argument is not an array.
     */
    private function extractCallArguments(array $args): array
    {
        $parameters = [];
        $opts = [];
        $streamCallback = null;

        if (!isset($args[0])) {
            return [$parameters, $opts, $streamCallback];
        }

        if (\is_array($args[0])) {
            $parameters = $args[0];

            if (isset($args[1]) && \is_array($args[1])) {
                $opts = $args[1];

                if (isset($args[2]) && \is_callable($args[2])) {
                    $streamCallback = $args[2];
                }
            } elseif (isset($args[1]) && \is_callable($args[1])) {
                $streamCallback = $args[1];
            }
        } else {
            throw new InvalidArgumentException('First argument must be an array of parameters.');
        }

        return [$parameters, $opts, $streamCallback];
    }

    /**
     * Calls the MistralAI API with the provided method, key, parameters, and options.
     *
     * @param string $method The HTTP method for the request.
     * @param string $key The API endpoint key.
     * @param array $parameters Parameters for URL placeholders.
     * @param array $opts The options for the request body or query.
     * @param callable|null $streamCallback Callback function to handle streaming data.
     *
     * @return ResponseInterface|null The API response or null if streaming.
     *
     * @throws MistralAIException If the API returns an error.
     * @throws RandomException
     */
    private function callAPI(
        string $method,
        string $key,
        array $parameters = [],
        array $opts = [],
        ?callable $streamCallback = null
    ): ?ResponseInterface {
        $uri = MistralAIURLBuilder::createUrl(
            $this->uriFactory,
            $key,
            $parameters,
            $this->origin,
            $this->apiVersion
        );

        return $this->sendRequest($uri, $method, $opts, $streamCallback);
    }

    /**
     * Sends an HTTP request to the MistralAI API and returns the response.
     *
     * @param UriInterface $uri The URI to send the request to.
     * @param string $method The HTTP method to use.
     * @param array $params Parameters to include in the request body.
     * @param callable|null $streamCallback Callback function to handle streaming data.
     *
     * @return ResponseInterface|null The response from the MistralAI API or null if streaming.
     *
     * @throws MistralAIException If the API returns an error.
     * @throws RandomException
     */
    private function sendRequest(
        UriInterface $uri,
        string $method,
        array $params = [],
        ?callable $streamCallback = null
    ): ?ResponseInterface {
        $request = $this->requestFactory->createRequest($method, $uri);
        $isMultipart = $this->isMultipartRequest($params);
        $boundary = $isMultipart ? $this->generateMultipartBoundary() : null;
        $headers = $this->createHeaders($isMultipart, $boundary);
        $request = $this->applyHeaders($request, $headers);

        $body = $isMultipart
            ? $this->createMultipartStream($params, $boundary)
            : $this->createJsonBody($params);

        if ($body !== '') {
            $request = $request->withBody($this->streamFactory->createStream($body));
        }

        try {
            if ($streamCallback !== null && ($params['stream'] ?? false) === true) {
                $this->handleStreamingResponse($request, $streamCallback);

                return null;
            }

            $response = $this->httpClient->sendRequest($request);

            if ($response->getStatusCode() >= 400) {
                throw new MistralAIException($response->getBody()->getContents(), $response->getStatusCode());
            }

            return $response;
        } catch (ClientExceptionInterface $e) {
            throw new MistralAIException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Handles a streaming response from the API.
     *
     * @param RequestInterface $request        The request to send.
     * @param callable         $streamCallback The callback function to handle streaming data.
     *
     * @return void
     *
     * @throws MistralAIException If an error occurs during streaming.
     */
    private function handleStreamingResponse(RequestInterface $request, callable $streamCallback): void
    {
        try {
            $response = $this->httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                throw new MistralAIException($response->getBody()->getContents(), $statusCode);
            }

            $body = $response->getBody();
            $buffer = '';

            while (!$body->eof()) {
                $chunk = $body->read(8192);
                $buffer .= $chunk;

                while (($newlinePos = \strpos($buffer, "\n")) !== false) {
                    $line = \substr($buffer, 0, $newlinePos);
                    $buffer = \substr($buffer, $newlinePos + 1);

                    $data = \trim($line);

                    if ($data === '') {
                        continue;
                    }

                    if ($data === 'data: [DONE]') {
                        return;
                    }

                    if (\str_starts_with($data, 'data: ')) {
                        $json = \substr($data, 6);

                        try {
                            $decoded = \json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                            $streamCallback($decoded);
                        } catch (JsonException $e) {
                            throw new MistralAIException('JSON decode error: ' . $e->getMessage(), 0, $e);
                        }
                    }
                }
            }
        } catch (ClientExceptionInterface $e) {
            throw new MistralAIException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Generates a unique multipart boundary string.
     *
     * @return string The generated multipart boundary string.
     *
     * @throws RandomException
     */
    private function generateMultipartBoundary(): string
    {
        return '----MistralAI' . \bin2hex(\random_bytes(16));
    }

    /**
     * Creates the headers for an API request.
     *
     * @param bool        $isMultipart Indicates whether the request is multipart.
     * @param string|null $boundary    The multipart boundary string, if applicable.
     *
     * @return array An associative array of headers.
     */
    private function createHeaders(bool $isMultipart, ?string $boundary): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => $isMultipart
                ? "multipart/form-data; boundary={$boundary}"
                : 'application/json',
        ];
    }

    /**
     * Applies the headers to the given request.
     *
     * @param RequestInterface $request The request to apply headers to.
     * @param array            $headers An associative array of headers to apply.
     *
     * @return RequestInterface The request with headers applied.
     */
    private function applyHeaders(RequestInterface $request, array $headers): RequestInterface
    {
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        return $request;
    }

    /**
     * Creates a JSON-encoded body string from the given parameters.
     *
     * @param array $params An associative array of parameters to encode as JSON.
     *
     * @return string The JSON-encoded body string.
     *
     * @throws MistralAIException If JSON encoding fails.
     */
    private function createJsonBody(array $params): string
    {
        if (empty($params)) {
            return '';
        }

        try {
            return \json_encode($params, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new MistralAIException('JSON encode error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Creates a multipart stream for sending files in a request.
     *
     * @param array  $params   An associative array of parameters to send with the request.
     * @param string $boundary A string used as a boundary to separate parts of the multipart stream.
     *
     * @return string The multipart stream as a string.
     */
    private function createMultipartStream(array $params, string $boundary): string
    {
        $multipartStream = '';

        foreach ($params as $key => $value) {
            $multipartStream .= "--{$boundary}\r\n";
            $multipartStream .= "Content-Disposition: form-data; name=\"{$key}\"";

            if ($key === 'file' && \is_string($value) && \file_exists($value)) {
                $filename = \basename($value);
                $fileContents = \file_get_contents($value);
                $multipartStream .= "; filename=\"{$filename}\"\r\n";
                $multipartStream .= "Content-Type: application/octet-stream\r\n\r\n";
                $multipartStream .= "{$fileContents}\r\n";
            } else {
                $multipartStream .= "\r\n\r\n{$value}\r\n";
            }
        }

        $multipartStream .= "--{$boundary}--\r\n";

        return $multipartStream;
    }

    /**
     * Determines if a request is a multipart request based on the provided parameters.
     *
     * @param array $params An associative array of parameters to check.
     *
     * @return bool True if the request is a multipart request, false otherwise.
     */
    private function isMultipartRequest(array $params): bool
    {
        return isset($params['file']);
    }
}
