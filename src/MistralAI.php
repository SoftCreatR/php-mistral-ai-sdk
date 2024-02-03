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

use Exception;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use SensitiveParameter;
use SoftCreatR\MistralAI\Exception\MistralAIException;

use const JSON_THROW_ON_ERROR;

/**
 * A wrapper for the MistralAI API.
 *
 * @property string $apiKey
 * @property string $origin
 *
 * @method ResponseInterface createChatCompletion(array $options = [])
 * @method ResponseInterface createEmbedding(array $options = [])
 * @method ResponseInterface listModels()
 */
class MistralAI
{
    /**
     * The HTTP client instance used for sending requests.
     */
    private ClientInterface $httpClient;

    /**
     * The PSR-17 request factory instance used for creating requests.
     */
    private RequestFactoryInterface $requestFactory;

    /**
     * The PSR-17 stream factory instance used for creating request bodies.
     */
    private StreamFactoryInterface $streamFactory;

    /**
     * The PSR-17 URI factory instance used for creating URIs.
     */
    private UriFactoryInterface $uriFactory;

    /**
     * MistralAI API Key
     */
    public string $apiKey = '';

    /**
     * MistralAI API Origin (defaults to api.mistral.ai)
     */
    public string $origin = '';

    /**
     * MistralAI API Version (defaults to v1)
     */
    public string $apiVersion = '';

    public function __construct(
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        UriFactoryInterface $uriFactory,
        ClientInterface $httpClient,
        #[SensitiveParameter]
        string $apiKey,
        string $origin = '',
        string $apiVersion = ''
    ) {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->uriFactory = $uriFactory;
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->origin = $origin;
        $this->apiVersion = $apiVersion;
    }

    /**
     * Magic method to call the MistralAI API endpoints.
     *
     * @param string $key The endpoint method.
     * @param array $args The arguments for the endpoint method.
     *
     * @return ResponseInterface The API response.
     *
     * @throws MistralAIException If the API returns an error (HTTP status code >= 400).
     */
    public function __call(string $key, array $args): ResponseInterface
    {
        $endpoint = MistralAIURLBuilder::getEndpoint($key);
        $httpMethod = $endpoint['method'];

        [$parameter, $opts] = $this->extractCallArguments($args);

        return $this->callAPI($httpMethod, $key, $parameter, $opts);
    }

    /**
     * Extracts the arguments from the input array.
     *
     * @param array $args The input arguments.
     *
     * @return array An array containing the extracted parameter and options.
     */
    private function extractCallArguments(array $args): array
    {
        $parameter = null;
        $opts = [];

        if (!isset($args[0])) {
            return [$parameter, $opts];
        }

        if (\is_string($args[0])) {
            $parameter = $args[0];

            if (isset($args[1]) && \is_array($args[1])) {
                $opts = $args[1];
            }
        } elseif (\is_array($args[0])) {
            $opts = $args[0];
        }

        return [$parameter, $opts];
    }

    /**
     * Calls the MistralAI API with the provided method, key, parameter, and options.
     *
     * @param string $method The HTTP method for the request.
     * @param string $key The API endpoint key.
     * @param string|null $parameter An optional parameter for the request.
     * @param array $opts The options for the request.
     *
     * @return ResponseInterface The API response.
     *
     * @throws MistralAIException If the API returns an error (HTTP status code >= 400).
     */
    private function callAPI(string $method, string $key, ?string $parameter = null, array $opts = []): ResponseInterface
    {
        return $this->sendRequest(
            MistralAIURLBuilder::createUrl($this->uriFactory, $key, $parameter, $this->origin, $this->apiVersion),
            $method,
            $opts
        );
    }

    /**
     * Sends an HTTP request to the MistralAI API and returns the response.
     *
     * @param UriInterface $uri The URL to send the request to.
     * @param string $method The HTTP method to use (e.g., 'GET', 'POST', etc.).
     * @param array $params An associative array of parameters to send with the request (optional).
     *
     * @return ResponseInterface The response from the MistralAI API.
     *
     * @throws MistralAIException If the API returns an error (HTTP status code >= 400).
     * @throws Exception
     */
    private function sendRequest(UriInterface $uri, string $method, array $params = []): ResponseInterface
    {
        $request = $this->requestFactory->createRequest($method, $uri);

        $headers = $this->createHeaders();
        $request = $this->applyHeaders($request, $headers);

        $body = $this->createJsonBody($params);

        if (!empty($body)) {
            $request = $request->withBody($this->streamFactory->createStream($body));
        }

        try {
            $response = $this->httpClient->sendRequest($request);

            // Check if the response has a non-200 status code (error)
            if ($response->getStatusCode() >= 400) {
                throw new MistralAIException($response->getBody()->getContents(), $response->getStatusCode());
            }
        } catch (ClientExceptionInterface $e) {
            throw new MistralAIException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $response;
    }

    /**
     * Creates the headers for an API request.
     *
     * @return array An associative array of headers.
     */
    private function createHeaders(): array
    {
        return [
            'authorization' => 'Bearer ' . $this->apiKey,
            'content-type' => 'application/json',
        ];
    }

    /**
     * Applies the headers to the given request.
     *
     * @param RequestInterface $request The request to apply headers to.
     * @param array $headers An associative array of headers to apply.
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
     * Creates a JSON encoded body string from the given parameters.
     *
     * @param array $params An associative array of parameters to encode as JSON.
     *
     * @return string The JSON encoded body string, or an empty string if encoding fails.
     */
    private function createJsonBody(array $params): string
    {
        try {
            return !empty($params) ? \json_encode($params, JSON_THROW_ON_ERROR) : '';
        } catch (JsonException $e) {
            // Fallback to an empty string if encoding fails
            return '';
        }
    }
}
