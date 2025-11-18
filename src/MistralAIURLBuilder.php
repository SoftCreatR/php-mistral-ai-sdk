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

namespace SoftCreatR\MistralAI;

use InvalidArgumentException;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * Utility class for creating URLs for MistralAI API endpoints.
 */
final class MistralAIURLBuilder
{
    public const ORIGIN = 'api.mistral.ai';

    public const API_VERSION = 'v1';

    private const HTTP_METHOD_POST = 'POST';

    private const HTTP_METHOD_GET = 'GET';

    private const HTTP_METHOD_DELETE = 'DELETE';

    private const HTTP_METHOD_PATCH = 'PATCH';

    private const HTTP_METHOD_PUT = 'PUT';

    /**
     * Configuration of MistralAI API endpoints.
     *
     * @var array<string, array{method: string, path: string, streaming?: bool}>
     */
    private static array $urlEndpoints = [
        // Models
        'listModels' => ['method' => self::HTTP_METHOD_GET, 'path' => '/models'],
        'retrieveModel' => ['method' => self::HTTP_METHOD_GET, 'path' => '/models/{model_id}'],
        'deleteModel' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/models/{model_id}'],
        'updateFineTunedModel' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/fine_tuning/models/{model_id}'],
        'archiveModel' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/models/{model_id}/archive'],
        'unarchiveModel' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/fine_tuning/models/{model_id}/archive'],

        // Batch Jobs
        'listBatchJobs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/batch/jobs'],
        'createBatchJob' => ['method' => self::HTTP_METHOD_POST, 'path' => '/batch/jobs'],
        'retrieveBatchJob' => ['method' => self::HTTP_METHOD_GET, 'path' => '/batch/jobs/{job_id}'],
        'cancelBatchJob' => ['method' => self::HTTP_METHOD_POST, 'path' => '/batch/jobs/{job_id}/cancel'],

        // Files
        'uploadFile' => ['method' => self::HTTP_METHOD_POST, 'path' => '/files'],
        'listFiles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files'],
        'retrieveFile' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/{file_id}'],
        'deleteFile' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/files/{file_id}'],
        'downloadFile' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/{file_id}/content'],
        'retrieveFileSignedUrl' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/{file_id}/url'],

        // Fine-Tuning Jobs
        'listFineTuningJobs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/fine_tuning/jobs'],
        'retrieveFineTuningJob' => ['method' => self::HTTP_METHOD_GET, 'path' => '/fine_tuning/jobs/{job_id}'],
        'cancelFineTuningJob' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/jobs/{job_id}/cancel'],
        'startFineTuningJob' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/jobs/{job_id}/start'],
        'createFineTuningJob' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/jobs'],

        // Agents Completion
        'createAgentsCompletion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agents/completions'],

        // Agents (Beta)
        'listAgents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents'],
        'createAgent' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agents'],
        'retrieveAgent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/{agent_id}'],
        'deleteAgent' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/agents/{agent_id}'],
        'updateAgent' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/agents/{agent_id}'],
        'updateAgentVersion' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/agents/{agent_id}/version'],

        // Conversations (Beta)
        'listConversations' => ['method' => self::HTTP_METHOD_GET, 'path' => '/conversations'],
        'startConversation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations'],
        'startConversationStream' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations', 'streaming' => true],
        'retrieveConversation' => ['method' => self::HTTP_METHOD_GET, 'path' => '/conversations/{conversation_id}'],
        'appendConversation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations/{conversation_id}'],
        'appendConversationStream' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations/{conversation_id}', 'streaming' => true],
        'deleteConversation' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/conversations/{conversation_id}'],
        'listConversationHistory' => ['method' => self::HTTP_METHOD_GET, 'path' => '/conversations/{conversation_id}/history'],
        'listConversationMessages' => ['method' => self::HTTP_METHOD_GET, 'path' => '/conversations/{conversation_id}/messages'],
        'restartConversation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations/{conversation_id}/restart'],
        'restartConversationStream' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations/{conversation_id}/restart', 'streaming' => true],

        // Libraries (Beta)
        'listLibraries' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries'],
        'createLibrary' => ['method' => self::HTTP_METHOD_POST, 'path' => '/libraries'],
        'retrieveLibrary' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}'],
        'updateLibrary' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/libraries/{library_id}'],
        'deleteLibrary' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/libraries/{library_id}'],
        'listLibraryShares' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/share'],
        'upsertLibraryShare' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/libraries/{library_id}/share'],
        'deleteLibraryShare' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/libraries/{library_id}/share'],
        'listLibraryDocuments' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/documents'],
        'uploadLibraryDocument' => ['method' => self::HTTP_METHOD_POST, 'path' => '/libraries/{library_id}/documents'],
        'retrieveLibraryDocument' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/documents/{document_id}'],
        'updateLibraryDocument' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/libraries/{library_id}/documents/{document_id}'],
        'deleteLibraryDocument' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/libraries/{library_id}/documents/{document_id}'],
        'retrieveLibraryDocumentTextContent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/documents/{document_id}/text_content'],
        'retrieveLibraryDocumentStatus' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/documents/{document_id}/status'],
        'retrieveLibraryDocumentSignedUrl' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/documents/{document_id}/signed-url'],
        'retrieveLibraryDocumentExtractedTextSignedUrl' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/documents/{document_id}/extracted-text-signed-url'],
        'reprocessLibraryDocument' => ['method' => self::HTTP_METHOD_POST, 'path' => '/libraries/{library_id}/documents/{document_id}/reprocess'],

        // Files-derived resources (OCR, Audio, Embeddings, etc.)
        'createAudioTranscription' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/transcriptions'],
        'createAudioTranscriptionStream' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/transcriptions', 'streaming' => true],

        // Chat Completion
        'createChatCompletion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chat/completions'],

        // FIM Completion
        'createFimCompletion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fim/completions'],

        // Embeddings
        'createEmbedding' => ['method' => self::HTTP_METHOD_POST, 'path' => '/embeddings'],

        // Classifiers
        'createModeration' => ['method' => self::HTTP_METHOD_POST, 'path' => '/moderations'],
        'createChatModeration' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chat/moderations'],
        'createClassification' => ['method' => self::HTTP_METHOD_POST, 'path' => '/classifications'],
        'createChatClassification' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chat/classifications'],

        // OCR
        'createOcr' => ['method' => self::HTTP_METHOD_POST, 'path' => '/ocr'],
    ];

    /**
     * Prevents instantiation of this class.
     */
    private function __construct()
    {
        // This class should not be instantiated.
    }

    /**
     * Gets the MistralAI API endpoint configuration.
     *
     * @param string $key The endpoint key.
     *
     * @return array{method: string, path: string} The endpoint configuration.
     *
     * @throws InvalidArgumentException If the provided key is invalid.
     */
    public static function getEndpoint(string $key): array
    {
        if (!isset(self::$urlEndpoints[$key])) {
            throw new InvalidArgumentException(\sprintf('Invalid Mistral AI URL key "%s".', $key));
        }

        return self::$urlEndpoints[$key];
    }

    /**
     * Creates a URL for the specified MistralAI API endpoint.
     *
     * @param UriFactoryInterface  $uriFactory The PSR-17 URI factory instance used for creating URIs.
     * @param string               $key        The key representing the API endpoint.
     * @param array<string, mixed> $parameters Optional parameters to replace in the endpoint path.
     * @param string               $origin     Custom origin (hostname), if needed.
     * @param string               $apiVersion Custom API version, if different from the default.
     *
     * @return UriInterface The fully constructed URL for the API endpoint.
     *
     * @throws InvalidArgumentException If a required path parameter is missing or invalid.
     */
    public static function createUrl(
        UriFactoryInterface $uriFactory,
        string $key,
        array $parameters = [],
        string $origin = '',
        string $apiVersion = ''
    ): UriInterface {
        $endpoint = self::getEndpoint($key);
        $path = self::replacePathParameters($endpoint['path'], $parameters);

        return $uriFactory
            ->createUri()
            ->withScheme('https')
            ->withHost($origin !== '' ? $origin : self::ORIGIN)
            ->withPath('/' . ($apiVersion !== '' ? $apiVersion : self::API_VERSION) . $path);
    }

    /**
     * Replaces path parameters in the given path with provided parameter values.
     *
     * @param string              $path       The path containing parameter placeholders.
     * @param array<string, mixed> $parameters The parameter values to replace placeholders in the path.
     *
     * @return string The path with replaced parameter values.
     *
     * @throws InvalidArgumentException If a required path parameter is missing or invalid.
     */
    private static function replacePathParameters(string $path, array $parameters): string
    {
        return \preg_replace_callback('/\{(\w+)}/', static function ($matches) use ($parameters) {
            $key = $matches[1];

            if (!\array_key_exists($key, $parameters)) {
                throw new InvalidArgumentException(\sprintf('Missing path parameter "%s".', $key));
            }

            $value = $parameters[$key];

            if (!\is_scalar($value)) {
                throw new InvalidArgumentException(\sprintf(
                    'Parameter "%s" must be a scalar value, %s given.',
                    $key,
                    \gettype($value)
                ));
            }

            return (string)$value;
        }, $path);
    }
}
