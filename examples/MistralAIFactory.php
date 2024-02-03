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

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use SoftCreatR\MistralAI\MistralAI;

class MistralAIFactory
{
    // Replace 'your_api_key' with your actual MistralAI API key
    private const API_KEY = 'your_api_key';

    public static function create(#[SensitiveParameter] string $apiKey = self::API_KEY): MistralAI
    {
        $psr17Factory = new HttpFactory();
        $httpClient = new Client();

        return new MistralAI($psr17Factory, $psr17Factory, $psr17Factory, $httpClient, $apiKey);
    }

    public static function request(string $method, $args = []): void
    {
        // Instantiate the MistralAI class using the factory
        $mistralAI = self::create();

        // Execute request
        try {
            // Call the specified method
            $response = $mistralAI->{$method}($args);

            // Decode the response body
            $result = \json_decode(
                $response->getBody()->getContents(),
                true,
                512,
                \JSON_THROW_ON_ERROR
            );

            // Print the result information as a JSON string
            echo "============\n| Response |\n============\n\n";
            echo \json_encode($result, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
            echo "\n\n============\n";
        } catch (Exception $e) {
            // Handle any exceptions during the API call
            echo "Error: {$e->getMessage()}\n";
        }
    }
}
