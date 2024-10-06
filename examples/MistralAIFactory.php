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

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use SoftCreatR\MistralAI\MistralAI;
use SoftCreatR\MistralAI\MistralAIURLBuilder;

/**
 * Example factory class for creating and using the MistralAI client.
 *
 * Provides methods to instantiate the MistralAI client and send requests to the MistralAI API endpoints.
 */
final class MistralAIFactory
{
    /**
     * MistralAI API Key.
     *
     * @var string
     */
    private const API_KEY = 'your_api_key';

    /**
     * Prevents instantiation of this class.
     */
    private function __construct()
    {
        // This class should not be instantiated.
    }

    /**
     * Creates an instance of the MistralAI client.
     *
     * @param string $apiKey The MistralAI API key.
     *
     * @return MistralAI The MistralAI client instance.
     */
    public static function create(#[SensitiveParameter] string $apiKey = self::API_KEY): MistralAI
    {
        $psr17Factory = new HttpFactory();
        $httpClient = new Client([
            'stream' => true,
        ]);

        return new MistralAI(
            requestFactory: $psr17Factory,
            streamFactory: $psr17Factory,
            uriFactory: $psr17Factory,
            httpClient: $httpClient,
            apiKey: $apiKey
        );
    }

    /**
     * Sends a request to the specified MistralAI API endpoint.
     *
     * @param string         $method         The name of the API method to call.
     * @param array          $parameters     An associative array of parameters (URL parameters or request options).
     * @param callable|null  $streamCallback Optional callback function for streaming responses.
     *
     * @return void
     */
    public static function request(string $method, array $parameters = [], ?callable $streamCallback = null): void
    {
        $mistralAI = self::create();

        try {
            $endpoint = MistralAIURLBuilder::getEndpoint($method);
            $path = $endpoint['path'];

            // Determine if the path contains placeholders
            $hasPlaceholders = \preg_match('/\{(\w+)}/', $path) === 1;

            if ($hasPlaceholders) {
                $urlParameters = $parameters;
                $bodyOptions = [];
            } else {
                $urlParameters = [];
                $bodyOptions = $parameters;
            }

            if ($streamCallback !== null) {
                $mistralAI->{$method}($urlParameters, $bodyOptions, $streamCallback);
            } else {
                $response = $mistralAI->{$method}($urlParameters, $bodyOptions);

                $result = \json_decode(
                    $response->getBody()->getContents(),
                    true,
                    512,
                    \JSON_THROW_ON_ERROR
                );

                echo "============\n| Response |\n============\n\n";
                echo \json_encode($result, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
                echo "\n\n============\n";
            }
        } catch (Exception $e) {
            echo "Error: {$e->getMessage()}\n";
        }
    }
}
