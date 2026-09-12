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
use SoftCreatR\MistralAI\Exception\MistralAIException;
use SoftCreatR\MistralAI\MistralAI;
use SoftCreatR\MistralAI\MistralAIURLBuilder;

$projectRoot = \dirname(__DIR__);

if (\file_exists($projectRoot . '/.env')) {
    Dotenv::createImmutable($projectRoot)->load();
}

final class MistralAIFactory
{
    private function __construct() {}

    public static function create(
        #[\SensitiveParameter]
        string $apiKey = '',
    ): MistralAI {
        $psr17Factory = new HttpFactory();
        $apiVersion = $_ENV['MISTRAL_API_VERSION'] ?? '';

        if ($apiVersion === MistralAIURLBuilder::API_VERSION) {
            $apiVersion = '';
        }

        return new MistralAI(
            requestFactory: $psr17Factory,
            streamFactory: $psr17Factory,
            uriFactory: $psr17Factory,
            httpClient: new Client(['stream' => true]),
            apiKey: $apiKey,
            origin: $_ENV['MISTRAL_API_ORIGIN'] ?? '',
            apiVersion: $apiVersion,
        );
    }

    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $options
     * @param callable(array<string, mixed>):void|null $streamCallback
     *
     * @throws \RuntimeException If the required API key is not configured.
     */
    public static function request(
        string $method,
        array $parameters = [],
        array $options = [],
        ?callable $streamCallback = null,
        bool $returnResponse = false,
    ): ?string {
        try {
            $endpoint = MistralAIURLBuilder::getEndpoint($method);
            $keyName = ($endpoint['admin'] ?? false) ? 'MISTRAL_ADMIN_API_KEY' : 'MISTRAL_API_KEY';
            $apiKey = $_ENV[$keyName] ?? '';

            if ($apiKey === '') {
                throw new \RuntimeException("Set {$keyName} in the project .env file before running this example.");
            }

            $response = self::create($apiKey)->request(
                $method,
                $parameters,
                $options,
                $streamCallback,
            );

            if ($streamCallback !== null) {
                return null;
            }

            if ($response === null) {
                return null;
            }

            $body = $response->getBody()->getContents();

            if ($returnResponse) {
                return $body;
            }

            $contentType = $response->getHeaderLine('Content-Type');

            if (\str_contains($contentType, 'application/json')) {
                $decoded = \json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                echo \json_encode($decoded, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
            } else {
                echo $body;
            }
        } catch (MistralAIException $exception) {
            echo "Mistral AI API error: {$exception->getMessage()}\n";

            if ($exception->getRequestId() !== null) {
                echo "Request ID: {$exception->getRequestId()}\n";
            }
        } catch (\Throwable $exception) {
            echo "Error: {$exception->getMessage()}\n";
        }

        return null;
    }

    /**
     * @throws \RuntimeException If an example variable is not configured.
     */
    public static function env(string $name): string
    {
        $value = $_ENV[$name] ?? '';

        if ($value === '') {
            throw new \RuntimeException("Set {$name} in the project .env file before running this example.");
        }

        return $value;
    }

    /**
     * @throws \RuntimeException If the fixture does not exist.
     */
    public static function fixture(string $name): string
    {
        $path = __DIR__ . '/fixtures/' . $name;

        if (!\is_file($path)) {
            throw new \RuntimeException("Example fixture not found: {$path}");
        }

        return $path;
    }

    /**
     * @throws \RuntimeException If the fixture cannot be read.
     */
    public static function base64Fixture(string $name): string
    {
        $contents = \file_get_contents(self::fixture($name));

        if ($contents === false) {
            throw new \RuntimeException("Unable to read example fixture: {$name}");
        }

        return \base64_encode($contents);
    }
}
