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

use JsonException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use SoftCreatR\MistralAI\MistralAIURLBuilder;

#[CoversNothing]
final class EndpointCatalogTest extends TestCase
{
    /** @throws JsonException */
    public function testCatalogMatchesTheCurrentNonDeprecatedOpenApiRoutes(): void
    {
        // The reference also deprecates /agents/completions, despite its missing OpenAPI flag.
        $routes = [];
        foreach (MistralAIURLBuilder::getEndpoints() as $endpoint) {
            $routes[] = $endpoint['method'] . ' ' . ($endpoint['basePath'] ?? '/v1') . $endpoint['path'];
        }
        $routes = \array_values(\array_unique($routes));
        \sort($routes);

        $fixture = \file_get_contents(__DIR__ . '/fixtures/endpoints.json');
        $this->assertIsString($fixture);
        $this->assertSame(\json_decode($fixture, true, 512, JSON_THROW_ON_ERROR), $routes);
    }

    public function testEveryMethodHasAMatchingExampleAndReadmeEntry(): void
    {
        $root = \dirname(__DIR__);
        $readme = \file_get_contents($root . '/README.md');
        $this->assertIsString($readme);

        foreach (MistralAIURLBuilder::getEndpoints() as $method => $endpoint) {
            $path = "examples/{$endpoint['category']}/{$method}.php";
            $route = $endpoint['method'] . ' ' . ($endpoint['basePath'] ?? '/v1') . $endpoint['path'];
            $this->assertFileExists($root . '/' . $path);
            $example = \file_get_contents($root . '/' . $path);
            $this->assertIsString($example);
            $this->assertStringContainsString("'{$method}'", $example);
            $this->assertStringContainsString($route, $example);
            $this->assertStringContainsString("`{$method}`", $readme);
            $this->assertStringContainsString("`{$route}`", $readme);
            $this->assertStringContainsString("[PHP]({$path})", $readme);
        }
    }

    public function testRemovedMethodsHaveNoCompatibilityAliases(): void
    {
        $endpoints = MistralAIURLBuilder::getEndpoints();
        foreach ([
            'getChatCompletionEvents', 'getChatCompletionEventIds', 'getChatCompletionEvent',
            'getSimilarChatCompletionEvents', 'getChatCompletionFields', 'getChatCompletionFieldOptions',
            'getChatCompletionFieldOptionsCounts', 'judgeChatCompletionEvent', 'createCampaign', 'getCampaigns',
            'getCampaignById', 'deleteCampaign', 'getCampaignStatusById', 'getCampaignSelectedEvents',
            'postDatasetRecordsFromCampaign', 'postDatasetRecordsFromExplorer',
            'createOrUpdateConnectorOrganizationCredentials', 'createOrUpdateConnectorWorkspaceCredentials',
            'createOrUpdateConnectorUserCredentials', 'deleteConnectorOrganizationCredentials',
            'deleteConnectorWorkspaceCredentials', 'deleteConnectorUserCredentials',
            'createAgentsCompletion', 'listAgents', 'updateLibrary', 'updateLibraryDocument', 'callConnectorTool',
            'executeWorkflowRegistration', 'provisionAdminGroupToWorkspace',
        ] as $method) {
            $this->assertArrayNotHasKey($method, $endpoints);
        }
    }
}
