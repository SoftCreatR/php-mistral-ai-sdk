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

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use SoftCreatR\MistralAI\MistralAI;
use SoftCreatR\MistralAI\MistralAIURLBuilder;

// Load .env variables from the project root if available.
$projectRoot = \dirname(__DIR__);

if (\file_exists($projectRoot . '/.env')) {
    Dotenv::createImmutable($projectRoot)->load();
}

/**
 * Example factory class for creating and using the MistralAI client.
 */
final class MistralAIFactory
{
    private function __construct() {}

    /**
     * Create a configured MistralAI client.
     */
    public static function create(
        #[SensitiveParameter]
        string $apiKey = ''
    ): MistralAI {
        $psr17Factory = new HttpFactory();
        $httpClient = new Client(['stream' => true]);

        return new MistralAI(
            requestFactory: $psr17Factory,
            streamFactory: $psr17Factory,
            uriFactory: $psr17Factory,
            httpClient: $httpClient,
            apiKey: $apiKey,
            origin: $_ENV['MISTRAL_API_ORIGIN'] ?? '',
            apiVersion: $_ENV['MISTRAL_API_VERSION'] ?? '',
        );
    }

    /**
     * Generic helper to call any Mistral endpoint.
     *
     * @return mixed|null Returns decoded JSON by default, raw string when $returnResponse is true, or null for streams.
     */
    public static function request(
        string $method,
        array $parameters = [],
        array|callable|null $options = [],
        ?callable $streamCallback = null,
        bool $returnResponse = false
    ): mixed {
        $mistral = self::create($_ENV['MISTRAL_API_KEY'] ?? '');

        if (\is_callable($options) && $streamCallback === null) {
            $streamCallback = $options;
            $options = [];
        }

        if ($options === null) {
            $options = [];
        }

        try {
            $endpoint = MistralAIURLBuilder::getEndpoint($method);
            $hasPlaceholders = (bool)\preg_match('/\{\w+}/', $endpoint['path']);

            $urlParams = $hasPlaceholders ? $parameters : [];
            $bodyOpts = $hasPlaceholders ? $options : ($parameters + $options);

            $args = [];

            $normalizeQuery = static function (array $options): array {
                if (isset($options['query'])) {
                    $query = (array)$options['query'];
                    unset($options['query']);
                    $options['_query'] = $query;
                }

                return $options;
            };

            if ($hasPlaceholders) {
                $args[] = $urlParams;

                if (!empty($bodyOpts)) {
                    $bodyOpts = $normalizeQuery($bodyOpts);
                    $args[] = $bodyOpts;
                }
            } else {
                $bodyOpts = $normalizeQuery($bodyOpts);
                $args[] = $bodyOpts;
            }

            if ($streamCallback !== null) {
                $args[] = $streamCallback;
                $mistral->{$method}(...$args);

                return null;
            }

            $response = $mistral->{$method}(...$args);

            if ($returnResponse) {
                return $response->getBody()->getContents();
            }

            $contentType = $response->getHeaderLine('Content-Type');
            $body = $response->getBody()->getContents();

            if (\str_contains($contentType, 'application/json')) {
                $decoded = \json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
                echo "============\n| Response |\n============\n\n"
                    . \json_encode($decoded, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT)
                    . "\n\n============\n";
            } else {
                echo "Received response with Content-Type: {$contentType}\n";
                echo $body;
            }
        } catch (Exception $e) {
            echo "Error: {$e->getMessage()}\n";
        }

        return null;
    }
}
