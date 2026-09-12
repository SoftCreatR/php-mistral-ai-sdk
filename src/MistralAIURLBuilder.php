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
 * Utility class for creating URLs for Mistral AI API endpoints.
 */
class MistralAIURLBuilder
{
    public const ORIGIN = 'api.mistral.ai';

    public const API_VERSION = 'v1';

    public const BASE_PATH = '/v1';

    private const HTTP_METHOD_DELETE = 'DELETE';

    private const HTTP_METHOD_GET = 'GET';

    private const HTTP_METHOD_PATCH = 'PATCH';

    private const HTTP_METHOD_POST = 'POST';

    private const HTTP_METHOD_PUT = 'PUT';

    /**
     * @var array<string, array{
     *     method: string,
     *     path: string,
     *     category: string,
     *     body: 'none'|'json'|'multipart',
     *     basePath?: string,
     *     fileFields?: list<string>,
     *     headers?: array<string, string|string[]>,
     *     query?: array<string, bool|float|int|string>,
     *     streaming?: bool,
     *     admin?: bool,
     *     deprecated?: bool
     * }>
     */
    private static array $urlEndpoints = [

        // Prompts
        'listPrompts' => ['method' => self::HTTP_METHOD_GET, 'path' => '/prompts', 'category' => 'prompts', 'body' => 'none', 'basePath' => '/v2'],
        'createPrompt' => ['method' => self::HTTP_METHOD_POST, 'path' => '/prompts', 'category' => 'prompts', 'body' => 'json', 'basePath' => '/v2'],
        'retrievePrompt' => ['method' => self::HTTP_METHOD_GET, 'path' => '/prompts/{prompt_id}', 'category' => 'prompts', 'body' => 'none', 'basePath' => '/v2'],
        'deletePrompt' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/prompts/{prompt_id}', 'category' => 'prompts', 'body' => 'none', 'basePath' => '/v2'],
        'updatePrompt' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/prompts/{prompt_id}', 'category' => 'prompts', 'body' => 'json', 'basePath' => '/v2'],
        'listPromptVersions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/prompts/{prompt_id}/versions', 'category' => 'prompts', 'body' => 'none', 'basePath' => '/v2'],
        'createPromptVersion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/prompts/{prompt_id}/versions', 'category' => 'prompts', 'body' => 'json', 'basePath' => '/v2'],
        'retrievePromptVersion' => ['method' => self::HTTP_METHOD_GET, 'path' => '/prompts/{prompt_id}/versions/{version}', 'category' => 'prompts', 'body' => 'none', 'basePath' => '/v2'],
        'updatePromptVersionMetadata' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/prompts/{prompt_id}/versions/{version}', 'category' => 'prompts', 'body' => 'json', 'basePath' => '/v2'],

        // Skills
        'listSkills' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills', 'category' => 'skills', 'body' => 'none', 'basePath' => '/v2'],
        'createSkill' => ['method' => self::HTTP_METHOD_POST, 'path' => '/skills', 'category' => 'skills', 'body' => 'json', 'basePath' => '/v2'],
        'retrieveSkill' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills/{skill_id}', 'category' => 'skills', 'body' => 'none', 'basePath' => '/v2'],
        'deleteSkill' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/skills/{skill_id}', 'category' => 'skills', 'body' => 'none', 'basePath' => '/v2'],
        'updateSkill' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/skills/{skill_id}', 'category' => 'skills', 'body' => 'json', 'basePath' => '/v2'],
        'listSkillVersions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills/{skill_id}/versions', 'category' => 'skills', 'body' => 'none', 'basePath' => '/v2'],
        'createSkillVersion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/skills/{skill_id}/versions', 'category' => 'skills', 'body' => 'json', 'basePath' => '/v2'],
        'retrieveSkillVersion' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills/{skill_id}/versions/{version}', 'category' => 'skills', 'body' => 'none', 'basePath' => '/v2'],
        'updateSkillVersionMetadata' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/skills/{skill_id}/versions/{version}', 'category' => 'skills', 'body' => 'json', 'basePath' => '/v2'],

        // Audio
        'createSpeech' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/speech', 'category' => 'audio', 'body' => 'json'],

        // Models
        'listModels' => ['method' => self::HTTP_METHOD_GET, 'path' => '/models', 'category' => 'models', 'body' => 'none'],
        'retrieveModel' => ['method' => self::HTTP_METHOD_GET, 'path' => '/models/{model_id}', 'category' => 'models', 'body' => 'none'],
        'deleteModel' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/models/{model_id}', 'category' => 'models', 'body' => 'none'],

        // Conversations
        'startConversation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations', 'category' => 'conversations', 'body' => 'json'],
        'listConversations' => ['method' => self::HTTP_METHOD_GET, 'path' => '/conversations', 'category' => 'conversations', 'body' => 'none'],
        'retrieveConversation' => ['method' => self::HTTP_METHOD_GET, 'path' => '/conversations/{conversation_id}', 'category' => 'conversations', 'body' => 'none'],
        'deleteConversation' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/conversations/{conversation_id}', 'category' => 'conversations', 'body' => 'none'],
        'appendConversation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations/{conversation_id}', 'category' => 'conversations', 'body' => 'json'],
        'listConversationHistory' => ['method' => self::HTTP_METHOD_GET, 'path' => '/conversations/{conversation_id}/history', 'category' => 'conversations', 'body' => 'none'],
        'listConversationMessages' => ['method' => self::HTTP_METHOD_GET, 'path' => '/conversations/{conversation_id}/messages', 'category' => 'conversations', 'body' => 'none'],
        'restartConversation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations/{conversation_id}/restart', 'category' => 'conversations', 'body' => 'json'],

        // Agents
        'createAgent' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agents', 'category' => 'agents', 'body' => 'json'],
        'listAgents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents', 'category' => 'agents', 'body' => 'none'],
        'listAgentPages' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/pages', 'category' => 'agents', 'body' => 'none'],
        'retrieveAgent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/{agent_id}', 'category' => 'agents', 'body' => 'none'],
        'updateAgent' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/agents/{agent_id}', 'category' => 'agents', 'body' => 'json'],
        'deleteAgent' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/agents/{agent_id}', 'category' => 'agents', 'body' => 'none'],
        'updateAgentVersion' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/agents/{agent_id}/version', 'category' => 'agents', 'body' => 'none'],
        'listAgentVersions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/{agent_id}/versions', 'category' => 'agents', 'body' => 'none'],
        'retrieveAgentVersion' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/{agent_id}/versions/{version}', 'category' => 'agents', 'body' => 'none'],
        'upsertAgentVersionAlias' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/agents/{agent_id}/aliases', 'category' => 'agents', 'body' => 'none'],
        'listAgentVersionAliases' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agents/{agent_id}/aliases', 'category' => 'agents', 'body' => 'none'],
        'deleteAgentVersionAlias' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/agents/{agent_id}/aliases', 'category' => 'agents', 'body' => 'none'],

        // Conversations
        'startConversationStream' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations', 'category' => 'conversations', 'body' => 'json', 'streaming' => true],
        'appendConversationStream' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations/{conversation_id}', 'category' => 'conversations', 'body' => 'json', 'streaming' => true],
        'restartConversationStream' => ['method' => self::HTTP_METHOD_POST, 'path' => '/conversations/{conversation_id}/restart', 'category' => 'conversations', 'body' => 'json', 'streaming' => true],

        // Files
        'uploadFile' => ['method' => self::HTTP_METHOD_POST, 'path' => '/files', 'category' => 'files', 'body' => 'multipart', 'fileFields' => ['file']],
        'listFiles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files', 'category' => 'files', 'body' => 'none'],
        'retrieveFile' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/{file_id}', 'category' => 'files', 'body' => 'none'],
        'deleteFile' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/files/{file_id}', 'category' => 'files', 'body' => 'none'],
        'downloadFile' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/{file_id}/content', 'category' => 'files', 'body' => 'none'],
        'retrieveFileSignedUrl' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/{file_id}/url', 'category' => 'files', 'body' => 'none'],

        // Models
        'updateFineTunedModel' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/fine_tuning/models/{model_id}', 'category' => 'models', 'body' => 'json'],
        'archiveModel' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fine_tuning/models/{model_id}/archive', 'category' => 'models', 'body' => 'none'],
        'unarchiveModel' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/fine_tuning/models/{model_id}/archive', 'category' => 'models', 'body' => 'none'],

        // Batch
        'listBatchJobs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/batch/jobs', 'category' => 'batch', 'body' => 'none'],
        'createBatchJob' => ['method' => self::HTTP_METHOD_POST, 'path' => '/batch/jobs', 'category' => 'batch', 'body' => 'json'],
        'retrieveBatchJob' => ['method' => self::HTTP_METHOD_GET, 'path' => '/batch/jobs/{job_id}', 'category' => 'batch', 'body' => 'none'],
        'deleteBatchJob' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/batch/jobs/{job_id}', 'category' => 'batch', 'body' => 'none'],
        'cancelBatchJob' => ['method' => self::HTTP_METHOD_POST, 'path' => '/batch/jobs/{job_id}/cancel', 'category' => 'batch', 'body' => 'none'],

        // Chat
        'createChatCompletion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chat/completions', 'category' => 'chat', 'body' => 'json'],

        // Fim
        'createFimCompletion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/fim/completions', 'category' => 'fim', 'body' => 'json'],

        // Agents
        'createAgentsCompletion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agents/completions', 'category' => 'agents', 'body' => 'json'],

        // Embeddings
        'createEmbedding' => ['method' => self::HTTP_METHOD_POST, 'path' => '/embeddings', 'category' => 'embeddings', 'body' => 'json'],

        // Classifiers
        'createModeration' => ['method' => self::HTTP_METHOD_POST, 'path' => '/moderations', 'category' => 'classifiers', 'body' => 'json'],
        'createChatModeration' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chat/moderations', 'category' => 'classifiers', 'body' => 'json'],

        // Ocr
        'createOcr' => ['method' => self::HTTP_METHOD_POST, 'path' => '/ocr', 'category' => 'ocr', 'body' => 'json'],

        // Classifiers
        'createClassification' => ['method' => self::HTTP_METHOD_POST, 'path' => '/classifications', 'category' => 'classifiers', 'body' => 'json'],
        'createChatClassification' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chat/classifications', 'category' => 'classifiers', 'body' => 'json'],

        // Audio
        'createAudioTranscription' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/transcriptions', 'category' => 'audio', 'body' => 'multipart', 'fileFields' => ['file']],
        'createAudioTranscriptionStream' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/transcriptions', 'category' => 'audio', 'body' => 'multipart', 'fileFields' => ['file'], 'streaming' => true],

        // Libraries
        'listLibraries' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries', 'category' => 'libraries', 'body' => 'none'],
        'createLibrary' => ['method' => self::HTTP_METHOD_POST, 'path' => '/libraries', 'category' => 'libraries', 'body' => 'json'],
        'retrieveLibrary' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}', 'category' => 'libraries', 'body' => 'none'],
        'deleteLibrary' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/libraries/{library_id}', 'category' => 'libraries', 'body' => 'none'],
        'patchLibrary' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/libraries/{library_id}', 'category' => 'libraries', 'body' => 'json'],
        'updateLibrary' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/libraries/{library_id}', 'category' => 'libraries', 'body' => 'json'],

        // Libraries / Documents
        'listLibraryDocuments' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/documents', 'category' => 'libraries/documents', 'body' => 'none'],
        'uploadLibraryDocument' => ['method' => self::HTTP_METHOD_POST, 'path' => '/libraries/{library_id}/documents', 'category' => 'libraries/documents', 'body' => 'multipart', 'fileFields' => ['file']],
        'retrieveLibraryDocument' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/documents/{document_id}', 'category' => 'libraries/documents', 'body' => 'none'],
        'patchLibraryDocument' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/libraries/{library_id}/documents/{document_id}', 'category' => 'libraries/documents', 'body' => 'json'],
        'updateLibraryDocument' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/libraries/{library_id}/documents/{document_id}', 'category' => 'libraries/documents', 'body' => 'json'],
        'deleteLibraryDocument' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/libraries/{library_id}/documents/{document_id}', 'category' => 'libraries/documents', 'body' => 'none'],
        'retrieveLibraryDocumentTextContent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/documents/{document_id}/text_content', 'category' => 'libraries/documents', 'body' => 'none'],
        'retrieveLibraryDocumentStatus' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/documents/{document_id}/status', 'category' => 'libraries/documents', 'body' => 'none'],
        'retrieveLibraryDocumentSignedUrl' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/documents/{document_id}/signed-url', 'category' => 'libraries/documents', 'body' => 'none'],
        'retrieveLibraryDocumentExtractedTextSignedUrl' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/documents/{document_id}/extracted-text-signed-url', 'category' => 'libraries/documents', 'body' => 'none'],
        'reprocessLibraryDocument' => ['method' => self::HTTP_METHOD_POST, 'path' => '/libraries/{library_id}/documents/{document_id}/reprocess', 'category' => 'libraries/documents', 'body' => 'none'],

        // Libraries / Shares
        'listLibraryShares' => ['method' => self::HTTP_METHOD_GET, 'path' => '/libraries/{library_id}/share', 'category' => 'libraries/shares', 'body' => 'none'],
        'upsertLibraryShare' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/libraries/{library_id}/share', 'category' => 'libraries/shares', 'body' => 'json'],
        'deleteLibraryShare' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/libraries/{library_id}/share', 'category' => 'libraries/shares', 'body' => 'json'],

        // Observability / Chat-Completion-Events
        'getChatCompletionEvents' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/chat-completion-events/search', 'category' => 'observability/chat-completion-events', 'body' => 'json'],
        'getChatCompletionEventIds' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/chat-completion-events/search-ids', 'category' => 'observability/chat-completion-events', 'body' => 'json'],
        'getChatCompletionEvent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/chat-completion-events/{event_id}', 'category' => 'observability/chat-completion-events', 'body' => 'none'],
        'getSimilarChatCompletionEvents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/chat-completion-events/{event_id}/similar-events', 'category' => 'observability/chat-completion-events', 'body' => 'none'],

        // Observability / Chat-Completion-Events / Fields
        'getChatCompletionFields' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/chat-completion-fields', 'category' => 'observability/chat-completion-events/fields', 'body' => 'none'],
        'getChatCompletionFieldOptions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/chat-completion-fields/{field_name}/options', 'category' => 'observability/chat-completion-events/fields', 'body' => 'none'],
        'getChatCompletionFieldOptionsCounts' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/chat-completion-fields/{field_name}/options-counts', 'category' => 'observability/chat-completion-events/fields', 'body' => 'json'],

        // Observability / Chat-Completion-Events
        'judgeChatCompletionEvent' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/chat-completion-events/{event_id}/live-judging', 'category' => 'observability/chat-completion-events', 'body' => 'json'],

        // Observability / Judges
        'createJudge' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/judges', 'category' => 'observability/judges', 'body' => 'json'],
        'getJudges' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/judges', 'category' => 'observability/judges', 'body' => 'none'],
        'getJudgeById' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/judges/{judge_id}', 'category' => 'observability/judges', 'body' => 'none'],
        'deleteJudge' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/observability/judges/{judge_id}', 'category' => 'observability/judges', 'body' => 'none'],
        'updateJudge' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/observability/judges/{judge_id}', 'category' => 'observability/judges', 'body' => 'json'],
        'judgeConversation' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/judges/{judge_id}/live-judging', 'category' => 'observability/judges', 'body' => 'json'],

        // Observability / Campaigns
        'createCampaign' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/campaigns', 'category' => 'observability/campaigns', 'body' => 'json'],
        'getCampaigns' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/campaigns', 'category' => 'observability/campaigns', 'body' => 'none'],
        'getCampaignById' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/campaigns/{campaign_id}', 'category' => 'observability/campaigns', 'body' => 'none'],
        'deleteCampaign' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/observability/campaigns/{campaign_id}', 'category' => 'observability/campaigns', 'body' => 'none'],
        'getCampaignStatusById' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/campaigns/{campaign_id}/status', 'category' => 'observability/campaigns', 'body' => 'none'],
        'getCampaignSelectedEvents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/campaigns/{campaign_id}/selected-events', 'category' => 'observability/campaigns', 'body' => 'none'],

        // Observability / Datasets
        'createDataset' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/datasets', 'category' => 'observability/datasets', 'body' => 'json'],
        'getDatasets' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/datasets', 'category' => 'observability/datasets', 'body' => 'none'],
        'getDatasetById' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/datasets/{dataset_id}', 'category' => 'observability/datasets', 'body' => 'none'],
        'deleteDataset' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/observability/datasets/{dataset_id}', 'category' => 'observability/datasets', 'body' => 'none'],
        'updateDataset' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/observability/datasets/{dataset_id}', 'category' => 'observability/datasets', 'body' => 'json'],
        'getDatasetRecords' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/datasets/{dataset_id}/records', 'category' => 'observability/datasets', 'body' => 'none'],
        'createDatasetRecord' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/datasets/{dataset_id}/records', 'category' => 'observability/datasets', 'body' => 'json'],
        'postDatasetRecordsFromCampaign' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/datasets/{dataset_id}/imports/from-campaign', 'category' => 'observability/datasets', 'body' => 'json'],
        'postDatasetRecordsFromExplorer' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/datasets/{dataset_id}/imports/from-explorer', 'category' => 'observability/datasets', 'body' => 'json'],
        'postDatasetRecordsFromFile' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/datasets/{dataset_id}/imports/from-file', 'category' => 'observability/datasets', 'body' => 'json'],
        'postDatasetRecordsFromPlayground' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/datasets/{dataset_id}/imports/from-playground', 'category' => 'observability/datasets', 'body' => 'json'],
        'postDatasetRecordsFromDataset' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/datasets/{dataset_id}/imports/from-dataset', 'category' => 'observability/datasets', 'body' => 'json'],
        'exportDatasetToJsonl' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/datasets/{dataset_id}/exports/to-jsonl', 'category' => 'observability/datasets', 'body' => 'none'],
        'getDatasetImportTask' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/datasets/{dataset_id}/tasks/{task_id}', 'category' => 'observability/datasets', 'body' => 'none'],
        'getDatasetImportTasks' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/datasets/{dataset_id}/tasks', 'category' => 'observability/datasets', 'body' => 'none'],

        // Observability / Datasets / Records
        'getDatasetRecord' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/dataset-records/{dataset_record_id}', 'category' => 'observability/datasets/records', 'body' => 'none'],
        'deleteDatasetRecord' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/observability/dataset-records/{dataset_record_id}', 'category' => 'observability/datasets/records', 'body' => 'none'],
        'deleteDatasetRecords' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/dataset-records/bulk-delete', 'category' => 'observability/datasets/records', 'body' => 'json'],
        'judgeDatasetRecord' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/dataset-records/{dataset_record_id}/live-judging', 'category' => 'observability/datasets/records', 'body' => 'json'],
        'updateDatasetRecordPayload' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/observability/dataset-records/{dataset_record_id}/payload', 'category' => 'observability/datasets/records', 'body' => 'json'],
        'updateDatasetRecordProperties' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/observability/dataset-records/{dataset_record_id}/properties', 'category' => 'observability/datasets/records', 'body' => 'json'],

        // Observability / Logs
        'searchLogs' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/logs/search', 'category' => 'observability/logs', 'body' => 'json'],

        // Observability / Traces
        'searchTraces' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/traces/search', 'category' => 'observability/traces', 'body' => 'json'],
        'aggregateTraces' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/traces/aggregate', 'category' => 'observability/traces', 'body' => 'json'],

        // Observability / Spans
        'searchSpans' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/spans/search', 'category' => 'observability/spans', 'body' => 'json'],
        'aggregateSpans' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/spans/aggregate', 'category' => 'observability/spans', 'body' => 'json'],
        'searchSpanEvaluations' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/spans/evaluations/search', 'category' => 'observability/spans', 'body' => 'json'],
        'searchLatestSpanEvaluations' => ['method' => self::HTTP_METHOD_POST, 'path' => '/observability/spans/evaluations/search/latest', 'category' => 'observability/spans', 'body' => 'json'],

        // Observability / Traces
        'getTraceFields' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/traces/fields', 'category' => 'observability/traces', 'body' => 'none'],
        'getTraceById' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/traces/{trace_id}', 'category' => 'observability/traces', 'body' => 'none'],
        'getTraceSpans' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/traces/{trace_id}/spans', 'category' => 'observability/traces', 'body' => 'none'],

        // Observability / Logs
        'getLogFields' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/logs/fields', 'category' => 'observability/logs', 'body' => 'none'],

        // Observability / Spans
        'getSpanFields' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/spans/fields', 'category' => 'observability/spans', 'body' => 'none'],
        'getSpanEvaluationFields' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/spans/evaluations/fields', 'category' => 'observability/spans', 'body' => 'none'],

        // Observability / Traces
        'getTraceFieldOptions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/traces/fields/{field_name}/options', 'category' => 'observability/traces', 'body' => 'none'],

        // Observability / Logs
        'getLogFieldOptions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/logs/fields/{field_name}/options', 'category' => 'observability/logs', 'body' => 'none'],

        // Observability / Spans
        'getSpanFieldOptions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/spans/fields/{field_name}/options', 'category' => 'observability/spans', 'body' => 'none'],
        'getSpanEvaluationFieldOptions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/spans/evaluations/fields/{field_name}/options', 'category' => 'observability/spans', 'body' => 'none'],

        // Observability / Traces
        'getSpanById' => ['method' => self::HTTP_METHOD_GET, 'path' => '/observability/traces/{trace_id}/spans/{span_id}', 'category' => 'observability/traces', 'body' => 'none'],

        // Connectors
        'createConnector' => ['method' => self::HTTP_METHOD_POST, 'path' => '/connectors', 'category' => 'connectors', 'body' => 'json'],
        'listConnectors' => ['method' => self::HTTP_METHOD_GET, 'path' => '/connectors', 'category' => 'connectors', 'body' => 'none'],
        'getConnectorAuthUrl' => ['method' => self::HTTP_METHOD_GET, 'path' => '/connectors/{connector_id_or_name}/auth_url', 'category' => 'connectors', 'body' => 'none'],
        'shareConnector' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/connectors/{connector_id}/share', 'category' => 'connectors', 'body' => 'none'],
        'unshareConnector' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/connectors/{connector_id}/share', 'category' => 'connectors', 'body' => 'none'],
        'activateForConsumerConnector' => ['method' => self::HTTP_METHOD_POST, 'path' => '/connectors/{connector_id}/{consumer_scope}/activate', 'category' => 'connectors', 'body' => 'none'],
        'deactivateForConsumerConnector' => ['method' => self::HTTP_METHOD_POST, 'path' => '/connectors/{connector_id}/{consumer_scope}/deactivate', 'category' => 'connectors', 'body' => 'none'],
        'callConnectorTool' => ['method' => self::HTTP_METHOD_POST, 'path' => '/connectors/{connector_id_or_name}/tools/{tool_name}/call', 'category' => 'connectors', 'body' => 'json'],
        'listConnectorTools' => ['method' => self::HTTP_METHOD_GET, 'path' => '/connectors/{connector_id_or_name}/tools', 'category' => 'connectors', 'body' => 'none'],
        'getConnectorAuthenticationMethods' => ['method' => self::HTTP_METHOD_GET, 'path' => '/connectors/{connector_id_or_name}/authentication_methods', 'category' => 'connectors', 'body' => 'none'],
        'listConnectorOrganizationCredentials' => ['method' => self::HTTP_METHOD_GET, 'path' => '/connectors/{connector_id_or_name}/organization/credentials', 'category' => 'connectors', 'body' => 'none'],
        'createOrUpdateConnectorOrganizationCredentials' => ['method' => self::HTTP_METHOD_POST, 'path' => '/connectors/{connector_id_or_name}/organization/credentials', 'category' => 'connectors', 'body' => 'json'],
        'listConnectorWorkspaceCredentials' => ['method' => self::HTTP_METHOD_GET, 'path' => '/connectors/{connector_id_or_name}/workspace/credentials', 'category' => 'connectors', 'body' => 'none'],
        'createOrUpdateConnectorWorkspaceCredentials' => ['method' => self::HTTP_METHOD_POST, 'path' => '/connectors/{connector_id_or_name}/workspace/credentials', 'category' => 'connectors', 'body' => 'json'],
        'listConnectorUserCredentials' => ['method' => self::HTTP_METHOD_GET, 'path' => '/connectors/{connector_id_or_name}/user/credentials', 'category' => 'connectors', 'body' => 'none'],
        'createOrUpdateConnectorUserCredentials' => ['method' => self::HTTP_METHOD_POST, 'path' => '/connectors/{connector_id_or_name}/user/credentials', 'category' => 'connectors', 'body' => 'json'],
        'deleteAllConnectorUserCredentials' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/connectors/{connector_id_or_name}/user/credentials', 'category' => 'connectors', 'body' => 'none'],
        'deleteConnectorOrganizationCredentials' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/connectors/{connector_id_or_name}/organization/credentials/{credentials_name}', 'category' => 'connectors', 'body' => 'none'],
        'deleteConnectorWorkspaceCredentials' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/connectors/{connector_id_or_name}/workspace/credentials/{credentials_name}', 'category' => 'connectors', 'body' => 'none'],
        'deleteConnectorUserCredentials' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/connectors/{connector_id_or_name}/user/credentials/{credentials_name}', 'category' => 'connectors', 'body' => 'none'],
        'retrieveConnector' => ['method' => self::HTTP_METHOD_GET, 'path' => '/connectors/{connector_id_or_name}', 'category' => 'connectors', 'body' => 'none'],
        'updateConnector' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/connectors/{connector_id}', 'category' => 'connectors', 'body' => 'json'],
        'deleteConnector' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/connectors/{connector_id}', 'category' => 'connectors', 'body' => 'none'],

        // Audio
        'listVoices' => ['method' => self::HTTP_METHOD_GET, 'path' => '/audio/voices', 'category' => 'audio', 'body' => 'none'],
        'createVoice' => ['method' => self::HTTP_METHOD_POST, 'path' => '/audio/voices', 'category' => 'audio', 'body' => 'json'],
        'deleteVoice' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/audio/voices/{voice_id}', 'category' => 'audio', 'body' => 'none'],
        'updateVoice' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/audio/voices/{voice_id}', 'category' => 'audio', 'body' => 'json'],
        'getVoice' => ['method' => self::HTTP_METHOD_GET, 'path' => '/audio/voices/{voice_id}', 'category' => 'audio', 'body' => 'none'],
        'getVoiceSampleAudio' => ['method' => self::HTTP_METHOD_GET, 'path' => '/audio/voices/{voice_id}/sample', 'category' => 'audio', 'body' => 'none'],

        // Workflows / Executions
        'getWorkflowExecution' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/executions/{execution_id}', 'category' => 'workflows/executions', 'body' => 'none'],
        'getWorkflowExecutionHistory' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/executions/{execution_id}/history', 'category' => 'workflows/executions', 'body' => 'none'],
        'signalWorkflowExecution' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/executions/{execution_id}/signals', 'category' => 'workflows/executions', 'body' => 'json'],
        'queryWorkflowExecution' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/executions/{execution_id}/queries', 'category' => 'workflows/executions', 'body' => 'json'],
        'terminateWorkflowExecution' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/executions/{execution_id}/terminate', 'category' => 'workflows/executions', 'body' => 'none'],
        'batchTerminateWorkflowExecutions' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/executions/terminate', 'category' => 'workflows/executions', 'body' => 'json'],
        'cancelWorkflowExecution' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/executions/{execution_id}/cancel', 'category' => 'workflows/executions', 'body' => 'none'],
        'batchCancelWorkflowExecutions' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/executions/cancel', 'category' => 'workflows/executions', 'body' => 'json'],
        'resetWorkflow' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/executions/{execution_id}/reset', 'category' => 'workflows/executions', 'body' => 'json'],
        'updateWorkflowExecution' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/executions/{execution_id}/updates', 'category' => 'workflows/executions', 'body' => 'json'],
        'getWorkflowExecutionTraceInfo' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/executions/{execution_id}/trace/info', 'category' => 'workflows/executions', 'body' => 'none'],
        'getWorkflowExecutionTraceOtel' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/executions/{execution_id}/trace/otel', 'category' => 'workflows/executions', 'body' => 'none'],
        'getWorkflowExecutionTraceSummary' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/executions/{execution_id}/trace/summary', 'category' => 'workflows/executions', 'body' => 'none'],
        'getWorkflowExecutionTraceEvents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/executions/{execution_id}/trace/events', 'category' => 'workflows/executions', 'body' => 'none'],
        'streamWorkflowExecution' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/executions/{execution_id}/stream', 'category' => 'workflows/executions', 'body' => 'none', 'streaming' => true],
        'getWorkflowExecutionLogs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/executions/{execution_id}/logs', 'category' => 'workflows/executions', 'body' => 'none'],
        'streamWorkflowExecutionLogs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/executions/{execution_id}/logs/stream', 'category' => 'workflows/executions', 'body' => 'none', 'streaming' => true],

        // Workflows / Metrics
        'getWorkflowMetrics' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/{workflow_name}/metrics', 'category' => 'workflows/metrics', 'body' => 'none'],

        // Workflows / Runs
        'listRuns' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/runs', 'category' => 'workflows/runs', 'body' => 'none'],
        'getRun' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/runs/{run_id}', 'category' => 'workflows/runs', 'body' => 'none'],
        'getRunHistory' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/runs/{run_id}/history', 'category' => 'workflows/runs', 'body' => 'none'],

        // Workflows / Schedules
        'getSchedules' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/schedules', 'category' => 'workflows/schedules', 'body' => 'none'],
        'scheduleWorkflow' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/schedules', 'category' => 'workflows/schedules', 'body' => 'json'],
        'getSchedule' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/schedules/{schedule_id}', 'category' => 'workflows/schedules', 'body' => 'none'],
        'unscheduleWorkflow' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/workflows/schedules/{schedule_id}', 'category' => 'workflows/schedules', 'body' => 'none'],
        'updateSchedule' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/workflows/schedules/{schedule_id}', 'category' => 'workflows/schedules', 'body' => 'json'],
        'pauseSchedule' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/schedules/{schedule_id}/pause', 'category' => 'workflows/schedules', 'body' => 'json'],
        'resumeSchedule' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/schedules/{schedule_id}/resume', 'category' => 'workflows/schedules', 'body' => 'json'],
        'triggerSchedule' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/schedules/{schedule_id}/trigger', 'category' => 'workflows/schedules', 'body' => 'json'],

        // Workflows / Events
        'getStreamEvents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/events/stream', 'category' => 'workflows/events', 'body' => 'none', 'streaming' => true],
        'getWorkflowEvents' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/events/list', 'category' => 'workflows/events', 'body' => 'none'],

        // Workflows / Deployments
        'listDeployments' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/deployments', 'category' => 'workflows/deployments', 'body' => 'none'],
        'createDeployment' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/deployments', 'category' => 'workflows/deployments', 'body' => 'json'],
        'updateDeployment' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/workflows/deployments/{name}', 'category' => 'workflows/deployments', 'body' => 'json'],
        'deleteDeployment' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/workflows/deployments/{name}', 'category' => 'workflows/deployments', 'body' => 'none'],
        'getDeployment' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/deployments/{name}', 'category' => 'workflows/deployments', 'body' => 'none'],
        'stopDeployment' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/deployments/{name}/stop', 'category' => 'workflows/deployments', 'body' => 'none'],
        'startDeployment' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/deployments/{name}/start', 'category' => 'workflows/deployments', 'body' => 'none'],
        'restartDeployment' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/deployments/{name}/restart', 'category' => 'workflows/deployments', 'body' => 'none'],
        'listDeploymentWorkers' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/deployments/{name}/workers', 'category' => 'workflows/deployments', 'body' => 'none'],
        'getDeploymentLogs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/deployments/{name}/logs', 'category' => 'workflows/deployments', 'body' => 'none'],
        'streamDeploymentLogs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/deployments/{name}/logs/stream', 'category' => 'workflows/deployments', 'body' => 'none', 'streaming' => true],

        // Workflows
        'getWorkflows' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows', 'category' => 'workflows', 'body' => 'none'],
        'getWorkflowRegistrations' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/registrations', 'category' => 'workflows', 'body' => 'none'],
        'executeWorkflow' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/{workflow_identifier}/execute', 'category' => 'workflows', 'body' => 'json'],
        'executeWorkflowRegistration' => ['method' => self::HTTP_METHOD_POST, 'path' => '/workflows/registrations/{workflow_registration_id}/execute', 'category' => 'workflows', 'body' => 'json'],
        'getWorkflow' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/{workflow_identifier}', 'category' => 'workflows', 'body' => 'none'],
        'updateWorkflow' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/workflows/{workflow_identifier}', 'category' => 'workflows', 'body' => 'json'],
        'getWorkflowRegistration' => ['method' => self::HTTP_METHOD_GET, 'path' => '/workflows/registrations/{workflow_registration_id}', 'category' => 'workflows', 'body' => 'none'],
        'bulkArchiveWorkflows' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/workflows/archive', 'category' => 'workflows', 'body' => 'json'],
        'bulkUnarchiveWorkflows' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/workflows/unarchive', 'category' => 'workflows', 'body' => 'json'],
        'archiveWorkflow' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/workflows/{workflow_identifier}/archive', 'category' => 'workflows', 'body' => 'none'],
        'unarchiveWorkflow' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/workflows/{workflow_identifier}/unarchive', 'category' => 'workflows', 'body' => 'none'],

        // Rag / Ingestion
        'getConfigs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/rag/ingestion_pipeline_configurations', 'category' => 'rag/ingestion', 'body' => 'none'],
        'registerConfig' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/rag/ingestion_pipeline_configurations', 'category' => 'rag/ingestion', 'body' => 'json'],
        'updateRunInfo' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/rag/ingestion_pipeline_configurations/{id}/run_info', 'category' => 'rag/ingestion', 'body' => 'json'],

        // Rag / Search-Indexes
        'getDeploymentSummaries' => ['method' => self::HTTP_METHOD_GET, 'path' => '/rag/deployments', 'category' => 'rag/search-indexes', 'body' => 'none'],
        'registerDeployment' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/rag/deployments', 'category' => 'rag/search-indexes', 'body' => 'json'],
        'unregisterDeployment' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/rag/deployments/{deployment_id}', 'category' => 'rag/search-indexes', 'body' => 'none'],
        'updateIndexMetrics' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/rag/deployments/{deployment_id}/metrics', 'category' => 'rag/search-indexes', 'body' => 'json'],

        // Users
        'getIdentity' => ['method' => self::HTTP_METHOD_GET, 'path' => '/users/me', 'category' => 'users', 'body' => 'none'],
        'listOrganizations' => ['method' => self::HTTP_METHOD_GET, 'path' => '/users/me/organizations', 'category' => 'users', 'body' => 'none'],
        'listWorkspaces' => ['method' => self::HTTP_METHOD_GET, 'path' => '/users/me/workspaces', 'category' => 'users', 'body' => 'none'],
    ];

    /**
     * @var array<string, array{
     *     method: string,
     *     path: string,
     *     category: string,
     *     body: 'none'|'json'|'multipart',
     *     basePath?: string,
     *     fileFields?: list<string>,
     *     headers?: array<string, string|string[]>,
     *     query?: array<string, bool|float|int|string>,
     *     streaming?: bool,
     *     admin?: bool,
     *     deprecated?: bool
     * }>
     */
    private static array $adminEndpoints = [
        // Administration / Users
        'listAdminUsers' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/users', 'category' => 'administration/users', 'body' => 'none', 'admin' => true],
        'createAdminUsers' => ['method' => self::HTTP_METHOD_POST, 'path' => '/admin/users', 'category' => 'administration/users', 'body' => 'json', 'admin' => true],
        'inviteAdminUsers' => ['method' => self::HTTP_METHOD_POST, 'path' => '/admin/users-invite', 'category' => 'administration/users', 'body' => 'json', 'admin' => true],
        'listAdminInvite' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/users-invite', 'category' => 'administration/users', 'body' => 'none', 'admin' => true],
        'deleteAdminInvite' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/admin/users-invite/{invite_uuid}', 'category' => 'administration/users', 'body' => 'none', 'admin' => true],
        'updateAdminUser' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/admin/users/{user_id}', 'category' => 'administration/users', 'body' => 'json', 'admin' => true],
        'deleteAdminUser' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/admin/users/{user_id}', 'category' => 'administration/users', 'body' => 'none', 'admin' => true],
        'retrieveAdminUser' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/users/{user_id}', 'category' => 'administration/users', 'body' => 'none', 'admin' => true],
        'listAdminRoles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/roles', 'category' => 'administration/users', 'body' => 'none', 'admin' => true],

        // Administration / Workspaces
        'listAdminWorkspaces' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/workspaces', 'category' => 'administration/workspaces', 'body' => 'none', 'admin' => true],
        'createAdminWorkspace' => ['method' => self::HTTP_METHOD_POST, 'path' => '/admin/workspaces', 'category' => 'administration/workspaces', 'body' => 'json', 'admin' => true],
        'updateAdminWorkspaces' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/admin/workspaces/{workspace_uuid}', 'category' => 'administration/workspaces', 'body' => 'json', 'admin' => true],
        'deleteAdminWorkspaces' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/admin/workspaces/{workspace_uuid}', 'category' => 'administration/workspaces', 'body' => 'none', 'admin' => true],
        'addAdminUsersWorkspaces' => ['method' => self::HTTP_METHOD_POST, 'path' => '/admin/workspaces/{workspace_uuid}/add-users', 'category' => 'administration/workspaces', 'body' => 'json', 'admin' => true],
        'addAdminOrUpdateUsersWorkspaces' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/admin/workspaces/{workspace_uuid}/users', 'category' => 'administration/workspaces', 'body' => 'json', 'admin' => true],
        'removeAdminUsersWorkspaces' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/admin/workspaces/{workspace_uuid}/remove-users', 'category' => 'administration/workspaces', 'body' => 'json', 'admin' => true],

        // Administration / Billing
        'listAdminRateLimits' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/rate-limit', 'category' => 'administration/billing', 'body' => 'none', 'admin' => true],
        'listAdminSpendLimits' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/spend-limit', 'category' => 'administration/billing', 'body' => 'none', 'admin' => true],
        'updateAdminSpendLimits' => ['method' => self::HTTP_METHOD_POST, 'path' => '/admin/spend-limit', 'category' => 'administration/billing', 'body' => 'json', 'admin' => true],
        'listAdminUsage' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/usage', 'category' => 'administration/billing', 'body' => 'none', 'admin' => true],

        // Administration / Audit-Logs
        'listAdminAuditLogs' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/audit-logs', 'category' => 'administration/audit-logs', 'body' => 'none', 'admin' => true],

        // Administration / User-Groups
        'listAdminUserGroups' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/user-groups', 'category' => 'administration/user-groups', 'body' => 'none', 'admin' => true],
        'createAdminUserGroup' => ['method' => self::HTTP_METHOD_POST, 'path' => '/admin/user-groups', 'category' => 'administration/user-groups', 'body' => 'json', 'admin' => true],
        'provisionAdminGroupToWorkspace' => ['method' => self::HTTP_METHOD_POST, 'path' => '/admin/user-groups/provision-workspace', 'category' => 'administration/user-groups', 'body' => 'json', 'admin' => true],
        'retrieveAdminUserGroup' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/user-groups/{group_uuid}', 'category' => 'administration/user-groups', 'body' => 'none', 'admin' => true],
        'updateAdminUserGroup' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/admin/user-groups/{group_uuid}', 'category' => 'administration/user-groups', 'body' => 'json', 'admin' => true],
        'deleteAdminUserGroup' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/admin/user-groups/{group_uuid}', 'category' => 'administration/user-groups', 'body' => 'none', 'admin' => true],
        'retrieveAdminUserGroupMembers' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/user-groups/{group_uuid}/members', 'category' => 'administration/user-groups', 'body' => 'none', 'admin' => true],
        'assignAdminUsersToGroup' => ['method' => self::HTTP_METHOD_POST, 'path' => '/admin/user-groups/{group_uuid}/members', 'category' => 'administration/user-groups', 'body' => 'json', 'admin' => true],
        'removeAdminUsersFromGroup' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/admin/user-groups/{group_uuid}/members', 'category' => 'administration/user-groups', 'body' => 'json', 'admin' => true],
        'retrieveAdminGroupWorkspaceAssignments' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/user-groups/{group_uuid}/workspaces', 'category' => 'administration/user-groups', 'body' => 'none', 'admin' => true],
        'assignAdminGroupToWorkspace' => ['method' => self::HTTP_METHOD_POST, 'path' => '/admin/user-groups/{group_uuid}/workspaces', 'category' => 'administration/user-groups', 'body' => 'json', 'admin' => true],
        'updateAdminGroupWorkspaceAssignment' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/admin/user-groups/{group_uuid}/workspaces/{workspace_uuid}', 'category' => 'administration/user-groups', 'body' => 'json', 'admin' => true],
        'removeAdminGroupFromWorkspace' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/admin/user-groups/{group_uuid}/workspaces/{workspace_uuid}', 'category' => 'administration/user-groups', 'body' => 'none', 'admin' => true],
        'updateAdminUserGroupOrganizationRole' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/admin/user-groups/{group_uuid}/organization-role', 'category' => 'administration/user-groups', 'body' => 'json', 'admin' => true],
        'retrieveAdminNestedGroupsAdmin' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/user-groups/{group_uuid}/nested', 'category' => 'administration/user-groups', 'body' => 'none', 'admin' => true],
        'setAdminNestedGroupsAdmin' => ['method' => self::HTTP_METHOD_PATCH, 'path' => '/admin/user-groups/{group_uuid}/nested', 'category' => 'administration/user-groups', 'body' => 'json', 'admin' => true],

        // Administration / Api-Keys
        'listAdminApiKeys' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/api-keys', 'category' => 'administration/api-keys', 'body' => 'none', 'admin' => true],
        'createAdminApiKey' => ['method' => self::HTTP_METHOD_POST, 'path' => '/admin/api-keys', 'category' => 'administration/api-keys', 'body' => 'json', 'admin' => true],
        'deleteAdminApiKey' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/admin/api-keys/{key_id}', 'category' => 'administration/api-keys', 'body' => 'none', 'admin' => true],

        // Administration / Scim
        'triggerAdminScimSync' => ['method' => self::HTTP_METHOD_POST, 'path' => '/admin/scim/sync', 'category' => 'administration/scim', 'body' => 'json', 'admin' => true],
        'retrieveAdminScimSyncRun' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/scim/sync/{run_id}', 'category' => 'administration/scim', 'body' => 'none', 'admin' => true],

        // Administration / Vibe-Work-Analytics
        'usageAdminByUser' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/analytics/vibe/work/usage/by_user_stats', 'category' => 'administration/vibe-work-analytics', 'body' => 'none', 'admin' => true],
        'usageAdminByAgent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/analytics/vibe/work/usage/by_agent_stats', 'category' => 'administration/vibe-work-analytics', 'body' => 'none', 'admin' => true],
        'usageAdminOverTime' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/analytics/vibe/work/usage/by_time_stats', 'category' => 'administration/vibe-work-analytics', 'body' => 'none', 'admin' => true],

        // Administration / Vibe-Code-Analytics
        'usageAdminByWorkspace' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/analytics/vibe/code/usage/by_workspace', 'category' => 'administration/vibe-code-analytics', 'body' => 'none', 'admin' => true],
        'usageAdminByOrganization' => ['method' => self::HTTP_METHOD_GET, 'path' => '/admin/analytics/vibe/code/usage/by_organization', 'category' => 'administration/vibe-code-analytics', 'body' => 'none', 'admin' => true],
    ];

    protected function __construct()
    {
        // This class should not be instantiated.
    }

    /**
     * @return array<string, array{
     *     method: string,
     *     path: string,
     *     category: string,
     *     body: 'none'|'json'|'multipart',
     *     basePath?: string,
     *     fileFields?: list<string>,
     *     headers?: array<string, string|string[]>,
     *     query?: array<string, bool|float|int|string>,
     *     streaming?: bool,
     *     admin?: bool,
     *     deprecated?: bool
     * }>
     */
    public static function getEndpoints(): array
    {
        return self::$urlEndpoints + self::$adminEndpoints;
    }

    /**
     * @return array{
     *     method: string,
     *     path: string,
     *     category: string,
     *     body: 'none'|'json'|'multipart',
     *     basePath?: string,
     *     fileFields?: list<string>,
     *     headers?: array<string, string|string[]>,
     *     query?: array<string, bool|float|int|string>,
     *     streaming?: bool,
     *     admin?: bool,
     *     deprecated?: bool
     * }
     *
     * @throws InvalidArgumentException If the provided key is invalid.
     */
    public static function getEndpoint(string $key): array
    {
        if (isset(self::$urlEndpoints[$key])) {
            return self::$urlEndpoints[$key];
        }

        if (!isset(self::$adminEndpoints[$key])) {
            throw new InvalidArgumentException(\sprintf('Invalid Mistral AI URL key "%s".', $key));
        }

        return self::$adminEndpoints[$key];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @throws InvalidArgumentException If a required path parameter is missing or invalid.
     */
    public static function createUrl(
        UriFactoryInterface $uriFactory,
        string $key,
        array $parameters = [],
        string $origin = '',
        string $apiVersion = '',
    ): UriInterface {
        $endpoint = self::getEndpoint($key);
        $endpointPath = self::replacePathParameters($endpoint['path'], $parameters);
        $isAbsoluteOrigin = $origin !== '' && \preg_match('#^[a-z][a-z0-9+.-]*://#i', $origin) === 1;

        if ($isAbsoluteOrigin) {
            $uri = $uriFactory->createUri($origin);
            $originBasePath = $uri->getPath();
        } else {
            $uri = $uriFactory
                ->createUri()
                ->withScheme('https')
                ->withHost($origin !== '' ? $origin : self::ORIGIN);
            $originBasePath = '';
        }

        if ($apiVersion !== '') {
            $resolvedBasePath = '/' . \trim($apiVersion, '/');
        } elseif ($isAbsoluteOrigin && $originBasePath !== '') {
            $resolvedBasePath = $originBasePath;
        } else {
            $resolvedBasePath = $endpoint['basePath'] ?? self::BASE_PATH;
        }

        $path = \rtrim('/' . \trim($resolvedBasePath, '/'), '/') . '/' . \ltrim($endpointPath, '/');

        return $uri->withPath($path);
    }

    /**
     * @param array<string, mixed> $parameters
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
                    \gettype($value),
                ));
            }

            return \rawurlencode((string) $value);
        }, $path);
    }
}
