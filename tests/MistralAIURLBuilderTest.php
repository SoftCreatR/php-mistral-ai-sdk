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

use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use SoftCreatR\MistralAI\MistralAIURLBuilder;

use const PHP_QUERY_RFC3986;

/**
 * @covers \SoftCreatR\MistralAI\MistralAIURLBuilder
 */
final class MistralAIURLBuilderTest extends TestCase
{
    /** @throws ReflectionException */
    public function testConstructorIsCovered(): void
    {
        $constructor = TestHelper::getPrivateConstructor(MistralAIURLBuilder::class);
        $instance = (new ReflectionClass(MistralAIURLBuilder::class))->newInstanceWithoutConstructor();
        $constructor->invoke($instance);

        $this->assertInstanceOf(MistralAIURLBuilder::class, $instance);
    }

    public function testGetEndpointRejectsInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Mistral AI URL key "invalidKey".');

        MistralAIURLBuilder::getEndpoint('invalidKey');
    }

    public function testGetEndpointReturnsAdministrationEndpoint(): void
    {
        $endpoint = MistralAIURLBuilder::getEndpoint('listAdminUsers');

        $this->assertSame('/admin/users', $endpoint['path']);
        $this->assertTrue($endpoint['admin']);
    }

    public function testCreateUrlRejectsMissingPathParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing path parameter "model_id".');

        MistralAIURLBuilder::createUrl(new HttpFactory(), 'retrieveModel');
    }

    public function testCreateUrlRejectsNonScalarPathParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "model_id" must be a scalar value, array given.');

        MistralAIURLBuilder::createUrl(
            new HttpFactory(),
            'retrieveModel',
            ['model_id' => ['not', 'scalar']],
        );
    }

    public function testRegistryMirrorsTheCurrentPublicSurface(): void
    {
        $endpoints = MistralAIURLBuilder::getEndpoints();
        $routes = [];

        $this->assertCount(288, $endpoints);

        foreach ($endpoints as $name => $endpoint) {
            $this->assertContains($endpoint['method'], ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $name);
            $this->assertContains($endpoint['body'], ['none', 'json', 'multipart'], $name);
            $this->assertNotSame('', $endpoint['category'], $name);
            $examplePath = \dirname(__DIR__) . '/examples/' . $endpoint['category'] . '/' . $name . '.php';
            $this->assertFileExists($examplePath, $name);

            $example = \file_get_contents($examplePath);
            $this->assertIsString($example, $name);
            $this->assertStringContainsString("'{$name}'", $example, $name);

            if ($endpoint['body'] === 'multipart') {
                $this->assertArrayHasKey('fileFields', $endpoint, $name);
            }

            if (isset($endpoint['basePath'])) {
                $this->assertSame('/v2', $endpoint['basePath'], $name);
            }

            if (isset($endpoint['streaming'])) {
                $this->assertTrue($endpoint['streaming'], $name);
            }

            if (isset($endpoint['admin'])) {
                $this->assertTrue($endpoint['admin'], $name);
            }

            $query = isset($endpoint['query'])
                ? '?' . \http_build_query($endpoint['query'], '', '&', PHP_QUERY_RFC3986)
                : '';
            $route = $endpoint['method'] . ' ' . ($endpoint['basePath'] ?? '/v1') . $endpoint['path'] . $query;

            if ($endpoint['streaming'] ?? false) {
                $route .= '#stream';
            }

            $this->assertArrayNotHasKey($route, $routes, "Duplicate route registered by {$name}.");
            $routes[$route] = true;
        }

        $this->assertSame('/v2', $endpoints['listPrompts']['basePath']);
        $this->assertSame(['file'], $endpoints['uploadFile']['fileFields']);
        $this->assertTrue($endpoints['startConversationStream']['streaming']);
        $this->assertTrue($endpoints['listAdminUsers']['admin']);
        $this->assertArrayHasKey('searchTraces', $endpoints);
        $this->assertArrayHasKey('listConnectors', $endpoints);
        $this->assertArrayHasKey('executeWorkflow', $endpoints);
    }

    public function testCreateUrlEncodesPathSegments(): void
    {
        $uri = MistralAIURLBuilder::createUrl(
            new HttpFactory(),
            'retrieveModel',
            ['model_id' => 'custom/model name'],
        );

        $this->assertSame('/v1/models/custom%2Fmodel%20name', $uri->getPath());
    }

    public function testCreateUrlSupportsAbsoluteBaseUrlAndExplicitApiVersion(): void
    {
        $uri = MistralAIURLBuilder::createUrl(
            new HttpFactory(),
            'listModels',
            [],
            'http://localhost:8080/mistral/v1',
        );
        $overridden = MistralAIURLBuilder::createUrl(
            new HttpFactory(),
            'listModels',
            [],
            'http://localhost:8080/mistral/v1',
            '/compatible/v1',
        );

        $this->assertSame('http://localhost:8080/mistral/v1/models', (string) $uri);
        $this->assertSame('http://localhost:8080/compatible/v1/models', (string) $overridden);
    }

    public function testCreateUrlUsesEndpointSpecificV2BasePath(): void
    {
        $uri = MistralAIURLBuilder::createUrl(new HttpFactory(), 'listSkills');

        $this->assertSame('https://api.mistral.ai/v2/skills', (string) $uri);
    }
}
