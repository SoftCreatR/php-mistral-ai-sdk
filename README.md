# Mistral AI API SDK for PHP

[![Tests](https://img.shields.io/github/actions/workflow/status/SoftCreatR/php-mistral-ai-sdk/.github/workflows/validate-pr.yml?branch=main&label=tests)](https://github.com/SoftCreatR/php-mistral-ai-sdk/actions/workflows/validate-pr.yml)
[![Latest Release](https://img.shields.io/packagist/v/softcreatr/php-mistral-ai-sdk)](https://packagist.org/packages/softcreatr/php-mistral-ai-sdk)
[![PHP](https://img.shields.io/packagist/dependency-v/softcreatr/php-mistral-ai-sdk/php)](composer.json)
[![License](https://img.shields.io/badge/license-ISC-blue.svg)](LICENSE.md)

A lightweight, underrated PSR-17/PSR-18 client for the current [Mistral AI API](https://docs.mistral.ai/api), with examples for every exposed SDK method, SSE streaming, streamed multipart uploads, and structured errors.

## Requirements

- PHP 8.1 or newer. CI tests PHP 8.1, 8.2, 8.3, 8.4, and 8.5.
- A PSR-17 request, stream, and URI factory.
- A PSR-18 HTTP client.
- The JSON extension.

Guzzle is used below because it provides both the PSR-17 factories and a PSR-18 client implementation. The SDK itself depends only on the PSR interfaces, so other compliant implementations remain supported.

## Installation

```bash
composer require softcreatr/php-mistral-ai-sdk guzzlehttp/guzzle
```

## Client Setup

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use SoftCreatR\MistralAI\MistralAI;

$factory = new HttpFactory();
$mistral = new MistralAI(
    requestFactory: $factory,
    streamFactory: $factory,
    uriFactory: $factory,
    httpClient: new Client(['stream' => true]),
    apiKey: (string) getenv('MISTRAL_API_KEY'),
);
```

Keep API keys on the server and out of source control.

## Chat Completions

```php
use const JSON_THROW_ON_ERROR;

$response = $mistral->createChatCompletion([
    'model' => 'mistral-small-latest',
    'messages' => [
        ['role' => 'user', 'content' => 'Give me a one-sentence summary of PSR-18.'],
    ],
]);

$result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
echo $result['choices'][0]['message']['content'];
```

Endpoint methods return a PSR-7 `ResponseInterface`, including calls that deliver SSE events to a callback.

## Arguments

Body-only endpoints use a single body array:

```php
$mistral->createEmbedding([
    'model' => 'mistral-embed',
    'input' => 'A sentence to embed.',
]);
```

For endpoints with path parameters, a single combined array is the preferred v4 form. Path fields are separated from the request body using the endpoint template:

```php
$mistral->updateAgent([
    'agent_id' => 'ag_abc123',
    'description' => 'Updated description',
]);
```

The v3 two-array form remains supported for existing integrations:

```php
$mistral->updateAgent(
    ['agent_id' => 'ag_abc123'],
    ['description' => 'Updated description'],
);
```

For `GET` and `DELETE` methods, non-path values become RFC 3986 query parameters. Path parameter values are URL encoded. The explicit form is available when method names are determined at runtime:

```php
$response = $mistral->request(
    'listFiles',
    ['page' => 0, 'page_size' => 20],
    customHeaders: ['X-Trace-Id' => 'trace_abc123'],
);
```

## Streaming

Set `stream` to `true` and pass a callback. The decoder supports arbitrarily split chunks, CRLF and LF delimiters, comments, multiline data fields, final unterminated frames, and `[DONE]`.

```php
$mistral->createChatCompletion(
    [
        'model' => 'mistral-small-latest',
        'messages' => [['role' => 'user', 'content' => 'Write a short haiku about PHP.']],
        'stream' => true,
    ],
    static function (array $event): void {
        echo $event['choices'][0]['delta']['content'] ?? '';
    },
);
```

An ordinary JSON response is still returned when a callback is supplied without requesting an SSE stream. Inherently streaming endpoints select a `StreamingClientInterface` transport without requiring a body flag.

## File Uploads

Multipart endpoints accept readable local file paths. Files are copied as raw bytes into a temporary stream rather than being base64 encoded or assembled in one large PHP string.

```php
$response = $mistral->uploadFile([
    'file' => __DIR__ . '/batch.jsonl',
    'purpose' => 'batch',
]);
```

Nested non-file values are encoded using bracket notation.

## Errors

4xx and 5xx responses throw `MistralAIException`. The exception keeps the parsed API error, raw response body, response headers, status code, and `x-request-id` for diagnostics.

```php
use SoftCreatR\MistralAI\Exception\MistralAIException;

try {
    $mistral->retrieveModel(['model_id' => 'missing-model']);
} catch (MistralAIException $exception) {
    error_log(sprintf(
        'Mistral AI request %s failed (%d): %s',
        $exception->getRequestId() ?? 'unknown',
        $exception->getCode(),
        $exception->getMessage(),
    ));
}
```

PSR-18 transport failures are wrapped in `MistralAIException` and retain the original exception as `getPrevious()`.

## Examples

Examples load the ignored project-level `.env` through `examples/MistralAIFactory.php`:

```bash
cp .env.example .env
php examples/chat/createChatCompletion.php
```

Administration examples use `MISTRAL_ADMIN_API_KEY`. Resource-specific examples read IDs from the variables documented in `.env.example`.

## Supported Methods

The catalog follows the current [Mistral AI API reference](https://docs.mistral.ai/api). Beta endpoints remain grouped by their documented API area, and the v2 Prompts and Skills routes use their endpoint-specific base path automatically.

<!-- ENDPOINT CATALOG START -->

### Prompts

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listPrompts` | `GET /v2/prompts` | `none` | [PHP](examples/prompts/listPrompts.php) |
| `createPrompt` | `POST /v2/prompts` | `json` | [PHP](examples/prompts/createPrompt.php) |
| `retrievePrompt` | `GET /v2/prompts/{prompt_id}` | `none` | [PHP](examples/prompts/retrievePrompt.php) |
| `deletePrompt` | `DELETE /v2/prompts/{prompt_id}` | `none` | [PHP](examples/prompts/deletePrompt.php) |
| `updatePrompt` | `PATCH /v2/prompts/{prompt_id}` | `json` | [PHP](examples/prompts/updatePrompt.php) |
| `listPromptVersions` | `GET /v2/prompts/{prompt_id}/versions` | `none` | [PHP](examples/prompts/listPromptVersions.php) |
| `createPromptVersion` | `POST /v2/prompts/{prompt_id}/versions` | `json` | [PHP](examples/prompts/createPromptVersion.php) |
| `retrievePromptVersion` | `GET /v2/prompts/{prompt_id}/versions/{version}` | `none` | [PHP](examples/prompts/retrievePromptVersion.php) |
| `updatePromptVersionMetadata` | `PATCH /v2/prompts/{prompt_id}/versions/{version}` | `json` | [PHP](examples/prompts/updatePromptVersionMetadata.php) |

### Skills

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listSkills` | `GET /v2/skills` | `none` | [PHP](examples/skills/listSkills.php) |
| `createSkill` | `POST /v2/skills` | `json` | [PHP](examples/skills/createSkill.php) |
| `retrieveSkill` | `GET /v2/skills/{skill_id}` | `none` | [PHP](examples/skills/retrieveSkill.php) |
| `deleteSkill` | `DELETE /v2/skills/{skill_id}` | `none` | [PHP](examples/skills/deleteSkill.php) |
| `updateSkill` | `PATCH /v2/skills/{skill_id}` | `json` | [PHP](examples/skills/updateSkill.php) |
| `listSkillVersions` | `GET /v2/skills/{skill_id}/versions` | `none` | [PHP](examples/skills/listSkillVersions.php) |
| `createSkillVersion` | `POST /v2/skills/{skill_id}/versions` | `json` | [PHP](examples/skills/createSkillVersion.php) |
| `retrieveSkillVersion` | `GET /v2/skills/{skill_id}/versions/{version}` | `none` | [PHP](examples/skills/retrieveSkillVersion.php) |
| `updateSkillVersionMetadata` | `PATCH /v2/skills/{skill_id}/versions/{version}` | `json` | [PHP](examples/skills/updateSkillVersionMetadata.php) |

### Audio

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createSpeech` | `POST /v1/audio/speech` | `json` | [PHP](examples/audio/createSpeech.php) |
| `createAudioTranscription` | `POST /v1/audio/transcriptions` | `multipart` | [PHP](examples/audio/createAudioTranscription.php) |
| `createAudioTranscriptionStream` | `POST /v1/audio/transcriptions` | `multipart` | [PHP](examples/audio/createAudioTranscriptionStream.php) |
| `listVoices` | `GET /v1/audio/voices` | `none` | [PHP](examples/audio/listVoices.php) |
| `createVoice` | `POST /v1/audio/voices` | `json` | [PHP](examples/audio/createVoice.php) |
| `deleteVoice` | `DELETE /v1/audio/voices/{voice_id}` | `none` | [PHP](examples/audio/deleteVoice.php) |
| `updateVoice` | `PATCH /v1/audio/voices/{voice_id}` | `json` | [PHP](examples/audio/updateVoice.php) |
| `getVoice` | `GET /v1/audio/voices/{voice_id}` | `none` | [PHP](examples/audio/getVoice.php) |
| `getVoiceSampleAudio` | `GET /v1/audio/voices/{voice_id}/sample` | `none` | [PHP](examples/audio/getVoiceSampleAudio.php) |

### Models

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listModels` | `GET /v1/models` | `none` | [PHP](examples/models/listModels.php) |
| `retrieveModel` | `GET /v1/models/{model_id}` | `none` | [PHP](examples/models/retrieveModel.php) |
| `deleteModel` | `DELETE /v1/models/{model_id}` | `none` | [PHP](examples/models/deleteModel.php) |
| `updateFineTunedModel` | `PATCH /v1/fine_tuning/models/{model_id}` | `json` | [PHP](examples/models/updateFineTunedModel.php) |
| `archiveModel` | `POST /v1/fine_tuning/models/{model_id}/archive` | `none` | [PHP](examples/models/archiveModel.php) |
| `unarchiveModel` | `DELETE /v1/fine_tuning/models/{model_id}/archive` | `none` | [PHP](examples/models/unarchiveModel.php) |

### Conversations

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `startConversation` | `POST /v1/conversations` | `json` | [PHP](examples/conversations/startConversation.php) |
| `listConversations` | `GET /v1/conversations` | `none` | [PHP](examples/conversations/listConversations.php) |
| `retrieveConversation` | `GET /v1/conversations/{conversation_id}` | `none` | [PHP](examples/conversations/retrieveConversation.php) |
| `deleteConversation` | `DELETE /v1/conversations/{conversation_id}` | `none` | [PHP](examples/conversations/deleteConversation.php) |
| `appendConversation` | `POST /v1/conversations/{conversation_id}` | `json` | [PHP](examples/conversations/appendConversation.php) |
| `listConversationHistory` | `GET /v1/conversations/{conversation_id}/history` | `none` | [PHP](examples/conversations/listConversationHistory.php) |
| `listConversationMessages` | `GET /v1/conversations/{conversation_id}/messages` | `none` | [PHP](examples/conversations/listConversationMessages.php) |
| `restartConversation` | `POST /v1/conversations/{conversation_id}/restart` | `json` | [PHP](examples/conversations/restartConversation.php) |
| `startConversationStream` | `POST /v1/conversations` | `json` | [PHP](examples/conversations/startConversationStream.php) |
| `appendConversationStream` | `POST /v1/conversations/{conversation_id}` | `json` | [PHP](examples/conversations/appendConversationStream.php) |
| `restartConversationStream` | `POST /v1/conversations/{conversation_id}/restart` | `json` | [PHP](examples/conversations/restartConversationStream.php) |

### Agents

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createAgent` | `POST /v1/agents` | `json` | [PHP](examples/agents/createAgent.php) |
| `listAgents` | `GET /v1/agents` | `none` | [PHP](examples/agents/listAgents.php) |
| `listAgentPages` | `GET /v1/agents/pages` | `none` | [PHP](examples/agents/listAgentPages.php) |
| `retrieveAgent` | `GET /v1/agents/{agent_id}` | `none` | [PHP](examples/agents/retrieveAgent.php) |
| `updateAgent` | `PATCH /v1/agents/{agent_id}` | `json` | [PHP](examples/agents/updateAgent.php) |
| `deleteAgent` | `DELETE /v1/agents/{agent_id}` | `none` | [PHP](examples/agents/deleteAgent.php) |
| `updateAgentVersion` | `PATCH /v1/agents/{agent_id}/version` | `none` | [PHP](examples/agents/updateAgentVersion.php) |
| `listAgentVersions` | `GET /v1/agents/{agent_id}/versions` | `none` | [PHP](examples/agents/listAgentVersions.php) |
| `retrieveAgentVersion` | `GET /v1/agents/{agent_id}/versions/{version}` | `none` | [PHP](examples/agents/retrieveAgentVersion.php) |
| `upsertAgentVersionAlias` | `PUT /v1/agents/{agent_id}/aliases` | `none` | [PHP](examples/agents/upsertAgentVersionAlias.php) |
| `listAgentVersionAliases` | `GET /v1/agents/{agent_id}/aliases` | `none` | [PHP](examples/agents/listAgentVersionAliases.php) |
| `deleteAgentVersionAlias` | `DELETE /v1/agents/{agent_id}/aliases` | `none` | [PHP](examples/agents/deleteAgentVersionAlias.php) |
| `createAgentsCompletion` | `POST /v1/agents/completions` | `json` | [PHP](examples/agents/createAgentsCompletion.php) |

### Files

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `uploadFile` | `POST /v1/files` | `multipart` | [PHP](examples/files/uploadFile.php) |
| `listFiles` | `GET /v1/files` | `none` | [PHP](examples/files/listFiles.php) |
| `retrieveFile` | `GET /v1/files/{file_id}` | `none` | [PHP](examples/files/retrieveFile.php) |
| `deleteFile` | `DELETE /v1/files/{file_id}` | `none` | [PHP](examples/files/deleteFile.php) |
| `downloadFile` | `GET /v1/files/{file_id}/content` | `none` | [PHP](examples/files/downloadFile.php) |
| `retrieveFileSignedUrl` | `GET /v1/files/{file_id}/url` | `none` | [PHP](examples/files/retrieveFileSignedUrl.php) |

### Batch

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listBatchJobs` | `GET /v1/batch/jobs` | `none` | [PHP](examples/batch/listBatchJobs.php) |
| `createBatchJob` | `POST /v1/batch/jobs` | `json` | [PHP](examples/batch/createBatchJob.php) |
| `retrieveBatchJob` | `GET /v1/batch/jobs/{job_id}` | `none` | [PHP](examples/batch/retrieveBatchJob.php) |
| `deleteBatchJob` | `DELETE /v1/batch/jobs/{job_id}` | `none` | [PHP](examples/batch/deleteBatchJob.php) |
| `cancelBatchJob` | `POST /v1/batch/jobs/{job_id}/cancel` | `none` | [PHP](examples/batch/cancelBatchJob.php) |

### Chat

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createChatCompletion` | `POST /v1/chat/completions` | `json` | [PHP](examples/chat/createChatCompletion.php) |

### Fim

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createFimCompletion` | `POST /v1/fim/completions` | `json` | [PHP](examples/fim/createFimCompletion.php) |

### Embeddings

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createEmbedding` | `POST /v1/embeddings` | `json` | [PHP](examples/embeddings/createEmbedding.php) |

### Classifiers

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createModeration` | `POST /v1/moderations` | `json` | [PHP](examples/classifiers/createModeration.php) |
| `createChatModeration` | `POST /v1/chat/moderations` | `json` | [PHP](examples/classifiers/createChatModeration.php) |
| `createClassification` | `POST /v1/classifications` | `json` | [PHP](examples/classifiers/createClassification.php) |
| `createChatClassification` | `POST /v1/chat/classifications` | `json` | [PHP](examples/classifiers/createChatClassification.php) |

### Ocr

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createOcr` | `POST /v1/ocr` | `json` | [PHP](examples/ocr/createOcr.php) |

### Libraries

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listLibraries` | `GET /v1/libraries` | `none` | [PHP](examples/libraries/listLibraries.php) |
| `createLibrary` | `POST /v1/libraries` | `json` | [PHP](examples/libraries/createLibrary.php) |
| `retrieveLibrary` | `GET /v1/libraries/{library_id}` | `none` | [PHP](examples/libraries/retrieveLibrary.php) |
| `deleteLibrary` | `DELETE /v1/libraries/{library_id}` | `none` | [PHP](examples/libraries/deleteLibrary.php) |
| `patchLibrary` | `PATCH /v1/libraries/{library_id}` | `json` | [PHP](examples/libraries/patchLibrary.php) |
| `updateLibrary` | `PUT /v1/libraries/{library_id}` | `json` | [PHP](examples/libraries/updateLibrary.php) |

### Libraries / Documents

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listLibraryDocuments` | `GET /v1/libraries/{library_id}/documents` | `none` | [PHP](examples/libraries/documents/listLibraryDocuments.php) |
| `uploadLibraryDocument` | `POST /v1/libraries/{library_id}/documents` | `multipart` | [PHP](examples/libraries/documents/uploadLibraryDocument.php) |
| `retrieveLibraryDocument` | `GET /v1/libraries/{library_id}/documents/{document_id}` | `none` | [PHP](examples/libraries/documents/retrieveLibraryDocument.php) |
| `patchLibraryDocument` | `PATCH /v1/libraries/{library_id}/documents/{document_id}` | `json` | [PHP](examples/libraries/documents/patchLibraryDocument.php) |
| `updateLibraryDocument` | `PUT /v1/libraries/{library_id}/documents/{document_id}` | `json` | [PHP](examples/libraries/documents/updateLibraryDocument.php) |
| `deleteLibraryDocument` | `DELETE /v1/libraries/{library_id}/documents/{document_id}` | `none` | [PHP](examples/libraries/documents/deleteLibraryDocument.php) |
| `retrieveLibraryDocumentTextContent` | `GET /v1/libraries/{library_id}/documents/{document_id}/text_content` | `none` | [PHP](examples/libraries/documents/retrieveLibraryDocumentTextContent.php) |
| `retrieveLibraryDocumentStatus` | `GET /v1/libraries/{library_id}/documents/{document_id}/status` | `none` | [PHP](examples/libraries/documents/retrieveLibraryDocumentStatus.php) |
| `retrieveLibraryDocumentSignedUrl` | `GET /v1/libraries/{library_id}/documents/{document_id}/signed-url` | `none` | [PHP](examples/libraries/documents/retrieveLibraryDocumentSignedUrl.php) |
| `retrieveLibraryDocumentExtractedTextSignedUrl` | `GET /v1/libraries/{library_id}/documents/{document_id}/extracted-text-signed-url` | `none` | [PHP](examples/libraries/documents/retrieveLibraryDocumentExtractedTextSignedUrl.php) |
| `reprocessLibraryDocument` | `POST /v1/libraries/{library_id}/documents/{document_id}/reprocess` | `none` | [PHP](examples/libraries/documents/reprocessLibraryDocument.php) |

### Libraries / Shares

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listLibraryShares` | `GET /v1/libraries/{library_id}/share` | `none` | [PHP](examples/libraries/shares/listLibraryShares.php) |
| `upsertLibraryShare` | `PUT /v1/libraries/{library_id}/share` | `json` | [PHP](examples/libraries/shares/upsertLibraryShare.php) |
| `deleteLibraryShare` | `DELETE /v1/libraries/{library_id}/share` | `json` | [PHP](examples/libraries/shares/deleteLibraryShare.php) |

### Observability / Chat Completion Events

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `getChatCompletionEvents` | `POST /v1/observability/chat-completion-events/search` | `json` | [PHP](examples/observability/chat-completion-events/getChatCompletionEvents.php) |
| `getChatCompletionEventIds` | `POST /v1/observability/chat-completion-events/search-ids` | `json` | [PHP](examples/observability/chat-completion-events/getChatCompletionEventIds.php) |
| `getChatCompletionEvent` | `GET /v1/observability/chat-completion-events/{event_id}` | `none` | [PHP](examples/observability/chat-completion-events/getChatCompletionEvent.php) |
| `getSimilarChatCompletionEvents` | `GET /v1/observability/chat-completion-events/{event_id}/similar-events` | `none` | [PHP](examples/observability/chat-completion-events/getSimilarChatCompletionEvents.php) |
| `judgeChatCompletionEvent` | `POST /v1/observability/chat-completion-events/{event_id}/live-judging` | `json` | [PHP](examples/observability/chat-completion-events/judgeChatCompletionEvent.php) |

### Observability / Chat Completion Events / Fields

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `getChatCompletionFields` | `GET /v1/observability/chat-completion-fields` | `none` | [PHP](examples/observability/chat-completion-events/fields/getChatCompletionFields.php) |
| `getChatCompletionFieldOptions` | `GET /v1/observability/chat-completion-fields/{field_name}/options` | `none` | [PHP](examples/observability/chat-completion-events/fields/getChatCompletionFieldOptions.php) |
| `getChatCompletionFieldOptionsCounts` | `POST /v1/observability/chat-completion-fields/{field_name}/options-counts` | `json` | [PHP](examples/observability/chat-completion-events/fields/getChatCompletionFieldOptionsCounts.php) |

### Observability / Judges

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createJudge` | `POST /v1/observability/judges` | `json` | [PHP](examples/observability/judges/createJudge.php) |
| `getJudges` | `GET /v1/observability/judges` | `none` | [PHP](examples/observability/judges/getJudges.php) |
| `getJudgeById` | `GET /v1/observability/judges/{judge_id}` | `none` | [PHP](examples/observability/judges/getJudgeById.php) |
| `deleteJudge` | `DELETE /v1/observability/judges/{judge_id}` | `none` | [PHP](examples/observability/judges/deleteJudge.php) |
| `updateJudge` | `PUT /v1/observability/judges/{judge_id}` | `json` | [PHP](examples/observability/judges/updateJudge.php) |
| `judgeConversation` | `POST /v1/observability/judges/{judge_id}/live-judging` | `json` | [PHP](examples/observability/judges/judgeConversation.php) |

### Observability / Campaigns

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createCampaign` | `POST /v1/observability/campaigns` | `json` | [PHP](examples/observability/campaigns/createCampaign.php) |
| `getCampaigns` | `GET /v1/observability/campaigns` | `none` | [PHP](examples/observability/campaigns/getCampaigns.php) |
| `getCampaignById` | `GET /v1/observability/campaigns/{campaign_id}` | `none` | [PHP](examples/observability/campaigns/getCampaignById.php) |
| `deleteCampaign` | `DELETE /v1/observability/campaigns/{campaign_id}` | `none` | [PHP](examples/observability/campaigns/deleteCampaign.php) |
| `getCampaignStatusById` | `GET /v1/observability/campaigns/{campaign_id}/status` | `none` | [PHP](examples/observability/campaigns/getCampaignStatusById.php) |
| `getCampaignSelectedEvents` | `GET /v1/observability/campaigns/{campaign_id}/selected-events` | `none` | [PHP](examples/observability/campaigns/getCampaignSelectedEvents.php) |

### Observability / Datasets

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createDataset` | `POST /v1/observability/datasets` | `json` | [PHP](examples/observability/datasets/createDataset.php) |
| `getDatasets` | `GET /v1/observability/datasets` | `none` | [PHP](examples/observability/datasets/getDatasets.php) |
| `getDatasetById` | `GET /v1/observability/datasets/{dataset_id}` | `none` | [PHP](examples/observability/datasets/getDatasetById.php) |
| `deleteDataset` | `DELETE /v1/observability/datasets/{dataset_id}` | `none` | [PHP](examples/observability/datasets/deleteDataset.php) |
| `updateDataset` | `PATCH /v1/observability/datasets/{dataset_id}` | `json` | [PHP](examples/observability/datasets/updateDataset.php) |
| `getDatasetRecords` | `GET /v1/observability/datasets/{dataset_id}/records` | `none` | [PHP](examples/observability/datasets/getDatasetRecords.php) |
| `createDatasetRecord` | `POST /v1/observability/datasets/{dataset_id}/records` | `json` | [PHP](examples/observability/datasets/createDatasetRecord.php) |
| `postDatasetRecordsFromCampaign` | `POST /v1/observability/datasets/{dataset_id}/imports/from-campaign` | `json` | [PHP](examples/observability/datasets/postDatasetRecordsFromCampaign.php) |
| `postDatasetRecordsFromExplorer` | `POST /v1/observability/datasets/{dataset_id}/imports/from-explorer` | `json` | [PHP](examples/observability/datasets/postDatasetRecordsFromExplorer.php) |
| `postDatasetRecordsFromFile` | `POST /v1/observability/datasets/{dataset_id}/imports/from-file` | `json` | [PHP](examples/observability/datasets/postDatasetRecordsFromFile.php) |
| `postDatasetRecordsFromPlayground` | `POST /v1/observability/datasets/{dataset_id}/imports/from-playground` | `json` | [PHP](examples/observability/datasets/postDatasetRecordsFromPlayground.php) |
| `postDatasetRecordsFromDataset` | `POST /v1/observability/datasets/{dataset_id}/imports/from-dataset` | `json` | [PHP](examples/observability/datasets/postDatasetRecordsFromDataset.php) |
| `exportDatasetToJsonl` | `GET /v1/observability/datasets/{dataset_id}/exports/to-jsonl` | `none` | [PHP](examples/observability/datasets/exportDatasetToJsonl.php) |
| `getDatasetImportTask` | `GET /v1/observability/datasets/{dataset_id}/tasks/{task_id}` | `none` | [PHP](examples/observability/datasets/getDatasetImportTask.php) |
| `getDatasetImportTasks` | `GET /v1/observability/datasets/{dataset_id}/tasks` | `none` | [PHP](examples/observability/datasets/getDatasetImportTasks.php) |

### Observability / Datasets / Records

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `getDatasetRecord` | `GET /v1/observability/dataset-records/{dataset_record_id}` | `none` | [PHP](examples/observability/datasets/records/getDatasetRecord.php) |
| `deleteDatasetRecord` | `DELETE /v1/observability/dataset-records/{dataset_record_id}` | `none` | [PHP](examples/observability/datasets/records/deleteDatasetRecord.php) |
| `deleteDatasetRecords` | `POST /v1/observability/dataset-records/bulk-delete` | `json` | [PHP](examples/observability/datasets/records/deleteDatasetRecords.php) |
| `judgeDatasetRecord` | `POST /v1/observability/dataset-records/{dataset_record_id}/live-judging` | `json` | [PHP](examples/observability/datasets/records/judgeDatasetRecord.php) |
| `updateDatasetRecordPayload` | `PUT /v1/observability/dataset-records/{dataset_record_id}/payload` | `json` | [PHP](examples/observability/datasets/records/updateDatasetRecordPayload.php) |
| `updateDatasetRecordProperties` | `PUT /v1/observability/dataset-records/{dataset_record_id}/properties` | `json` | [PHP](examples/observability/datasets/records/updateDatasetRecordProperties.php) |

### Observability / Logs

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `searchLogs` | `POST /v1/observability/logs/search` | `json` | [PHP](examples/observability/logs/searchLogs.php) |
| `getLogFields` | `GET /v1/observability/logs/fields` | `none` | [PHP](examples/observability/logs/getLogFields.php) |
| `getLogFieldOptions` | `GET /v1/observability/logs/fields/{field_name}/options` | `none` | [PHP](examples/observability/logs/getLogFieldOptions.php) |

### Observability / Traces

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `searchTraces` | `POST /v1/observability/traces/search` | `json` | [PHP](examples/observability/traces/searchTraces.php) |
| `aggregateTraces` | `POST /v1/observability/traces/aggregate` | `json` | [PHP](examples/observability/traces/aggregateTraces.php) |
| `getTraceFields` | `GET /v1/observability/traces/fields` | `none` | [PHP](examples/observability/traces/getTraceFields.php) |
| `getTraceById` | `GET /v1/observability/traces/{trace_id}` | `none` | [PHP](examples/observability/traces/getTraceById.php) |
| `getTraceSpans` | `GET /v1/observability/traces/{trace_id}/spans` | `none` | [PHP](examples/observability/traces/getTraceSpans.php) |
| `getTraceFieldOptions` | `GET /v1/observability/traces/fields/{field_name}/options` | `none` | [PHP](examples/observability/traces/getTraceFieldOptions.php) |
| `getSpanById` | `GET /v1/observability/traces/{trace_id}/spans/{span_id}` | `none` | [PHP](examples/observability/traces/getSpanById.php) |

### Observability / Spans

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `searchSpans` | `POST /v1/observability/spans/search` | `json` | [PHP](examples/observability/spans/searchSpans.php) |
| `aggregateSpans` | `POST /v1/observability/spans/aggregate` | `json` | [PHP](examples/observability/spans/aggregateSpans.php) |
| `searchSpanEvaluations` | `POST /v1/observability/spans/evaluations/search` | `json` | [PHP](examples/observability/spans/searchSpanEvaluations.php) |
| `searchLatestSpanEvaluations` | `POST /v1/observability/spans/evaluations/search/latest` | `json` | [PHP](examples/observability/spans/searchLatestSpanEvaluations.php) |
| `getSpanFields` | `GET /v1/observability/spans/fields` | `none` | [PHP](examples/observability/spans/getSpanFields.php) |
| `getSpanEvaluationFields` | `GET /v1/observability/spans/evaluations/fields` | `none` | [PHP](examples/observability/spans/getSpanEvaluationFields.php) |
| `getSpanFieldOptions` | `GET /v1/observability/spans/fields/{field_name}/options` | `none` | [PHP](examples/observability/spans/getSpanFieldOptions.php) |
| `getSpanEvaluationFieldOptions` | `GET /v1/observability/spans/evaluations/fields/{field_name}/options` | `none` | [PHP](examples/observability/spans/getSpanEvaluationFieldOptions.php) |

### Connectors

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createConnector` | `POST /v1/connectors` | `json` | [PHP](examples/connectors/createConnector.php) |
| `listConnectors` | `GET /v1/connectors` | `none` | [PHP](examples/connectors/listConnectors.php) |
| `getConnectorAuthUrl` | `GET /v1/connectors/{connector_id_or_name}/auth_url` | `none` | [PHP](examples/connectors/getConnectorAuthUrl.php) |
| `shareConnector` | `PUT /v1/connectors/{connector_id}/share` | `none` | [PHP](examples/connectors/shareConnector.php) |
| `unshareConnector` | `DELETE /v1/connectors/{connector_id}/share` | `none` | [PHP](examples/connectors/unshareConnector.php) |
| `activateForConsumerConnector` | `POST /v1/connectors/{connector_id}/{consumer_scope}/activate` | `none` | [PHP](examples/connectors/activateForConsumerConnector.php) |
| `deactivateForConsumerConnector` | `POST /v1/connectors/{connector_id}/{consumer_scope}/deactivate` | `none` | [PHP](examples/connectors/deactivateForConsumerConnector.php) |
| `callConnectorTool` | `POST /v1/connectors/{connector_id_or_name}/tools/{tool_name}/call` | `json` | [PHP](examples/connectors/callConnectorTool.php) |
| `listConnectorTools` | `GET /v1/connectors/{connector_id_or_name}/tools` | `none` | [PHP](examples/connectors/listConnectorTools.php) |
| `getConnectorAuthenticationMethods` | `GET /v1/connectors/{connector_id_or_name}/authentication_methods` | `none` | [PHP](examples/connectors/getConnectorAuthenticationMethods.php) |
| `listConnectorOrganizationCredentials` | `GET /v1/connectors/{connector_id_or_name}/organization/credentials` | `none` | [PHP](examples/connectors/listConnectorOrganizationCredentials.php) |
| `createOrUpdateConnectorOrganizationCredentials` | `POST /v1/connectors/{connector_id_or_name}/organization/credentials` | `json` | [PHP](examples/connectors/createOrUpdateConnectorOrganizationCredentials.php) |
| `listConnectorWorkspaceCredentials` | `GET /v1/connectors/{connector_id_or_name}/workspace/credentials` | `none` | [PHP](examples/connectors/listConnectorWorkspaceCredentials.php) |
| `createOrUpdateConnectorWorkspaceCredentials` | `POST /v1/connectors/{connector_id_or_name}/workspace/credentials` | `json` | [PHP](examples/connectors/createOrUpdateConnectorWorkspaceCredentials.php) |
| `listConnectorUserCredentials` | `GET /v1/connectors/{connector_id_or_name}/user/credentials` | `none` | [PHP](examples/connectors/listConnectorUserCredentials.php) |
| `createOrUpdateConnectorUserCredentials` | `POST /v1/connectors/{connector_id_or_name}/user/credentials` | `json` | [PHP](examples/connectors/createOrUpdateConnectorUserCredentials.php) |
| `deleteAllConnectorUserCredentials` | `DELETE /v1/connectors/{connector_id_or_name}/user/credentials` | `none` | [PHP](examples/connectors/deleteAllConnectorUserCredentials.php) |
| `deleteConnectorOrganizationCredentials` | `DELETE /v1/connectors/{connector_id_or_name}/organization/credentials/{credentials_name}` | `none` | [PHP](examples/connectors/deleteConnectorOrganizationCredentials.php) |
| `deleteConnectorWorkspaceCredentials` | `DELETE /v1/connectors/{connector_id_or_name}/workspace/credentials/{credentials_name}` | `none` | [PHP](examples/connectors/deleteConnectorWorkspaceCredentials.php) |
| `deleteConnectorUserCredentials` | `DELETE /v1/connectors/{connector_id_or_name}/user/credentials/{credentials_name}` | `none` | [PHP](examples/connectors/deleteConnectorUserCredentials.php) |
| `retrieveConnector` | `GET /v1/connectors/{connector_id_or_name}` | `none` | [PHP](examples/connectors/retrieveConnector.php) |
| `updateConnector` | `PATCH /v1/connectors/{connector_id}` | `json` | [PHP](examples/connectors/updateConnector.php) |
| `deleteConnector` | `DELETE /v1/connectors/{connector_id}` | `none` | [PHP](examples/connectors/deleteConnector.php) |

### Workflows / Executions

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `getWorkflowExecution` | `GET /v1/workflows/executions/{execution_id}` | `none` | [PHP](examples/workflows/executions/getWorkflowExecution.php) |
| `getWorkflowExecutionHistory` | `GET /v1/workflows/executions/{execution_id}/history` | `none` | [PHP](examples/workflows/executions/getWorkflowExecutionHistory.php) |
| `signalWorkflowExecution` | `POST /v1/workflows/executions/{execution_id}/signals` | `json` | [PHP](examples/workflows/executions/signalWorkflowExecution.php) |
| `queryWorkflowExecution` | `POST /v1/workflows/executions/{execution_id}/queries` | `json` | [PHP](examples/workflows/executions/queryWorkflowExecution.php) |
| `terminateWorkflowExecution` | `POST /v1/workflows/executions/{execution_id}/terminate` | `none` | [PHP](examples/workflows/executions/terminateWorkflowExecution.php) |
| `batchTerminateWorkflowExecutions` | `POST /v1/workflows/executions/terminate` | `json` | [PHP](examples/workflows/executions/batchTerminateWorkflowExecutions.php) |
| `cancelWorkflowExecution` | `POST /v1/workflows/executions/{execution_id}/cancel` | `none` | [PHP](examples/workflows/executions/cancelWorkflowExecution.php) |
| `batchCancelWorkflowExecutions` | `POST /v1/workflows/executions/cancel` | `json` | [PHP](examples/workflows/executions/batchCancelWorkflowExecutions.php) |
| `resetWorkflow` | `POST /v1/workflows/executions/{execution_id}/reset` | `json` | [PHP](examples/workflows/executions/resetWorkflow.php) |
| `updateWorkflowExecution` | `POST /v1/workflows/executions/{execution_id}/updates` | `json` | [PHP](examples/workflows/executions/updateWorkflowExecution.php) |
| `getWorkflowExecutionTraceInfo` | `GET /v1/workflows/executions/{execution_id}/trace/info` | `none` | [PHP](examples/workflows/executions/getWorkflowExecutionTraceInfo.php) |
| `getWorkflowExecutionTraceOtel` | `GET /v1/workflows/executions/{execution_id}/trace/otel` | `none` | [PHP](examples/workflows/executions/getWorkflowExecutionTraceOtel.php) |
| `getWorkflowExecutionTraceSummary` | `GET /v1/workflows/executions/{execution_id}/trace/summary` | `none` | [PHP](examples/workflows/executions/getWorkflowExecutionTraceSummary.php) |
| `getWorkflowExecutionTraceEvents` | `GET /v1/workflows/executions/{execution_id}/trace/events` | `none` | [PHP](examples/workflows/executions/getWorkflowExecutionTraceEvents.php) |
| `streamWorkflowExecution` | `GET /v1/workflows/executions/{execution_id}/stream` | `none` | [PHP](examples/workflows/executions/streamWorkflowExecution.php) |
| `getWorkflowExecutionLogs` | `GET /v1/workflows/executions/{execution_id}/logs` | `none` | [PHP](examples/workflows/executions/getWorkflowExecutionLogs.php) |
| `streamWorkflowExecutionLogs` | `GET /v1/workflows/executions/{execution_id}/logs/stream` | `none` | [PHP](examples/workflows/executions/streamWorkflowExecutionLogs.php) |

### Workflows / Metrics

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `getWorkflowMetrics` | `GET /v1/workflows/{workflow_name}/metrics` | `none` | [PHP](examples/workflows/metrics/getWorkflowMetrics.php) |

### Workflows / Runs

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listRuns` | `GET /v1/workflows/runs` | `none` | [PHP](examples/workflows/runs/listRuns.php) |
| `getRun` | `GET /v1/workflows/runs/{run_id}` | `none` | [PHP](examples/workflows/runs/getRun.php) |
| `getRunHistory` | `GET /v1/workflows/runs/{run_id}/history` | `none` | [PHP](examples/workflows/runs/getRunHistory.php) |

### Workflows / Schedules

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `getSchedules` | `GET /v1/workflows/schedules` | `none` | [PHP](examples/workflows/schedules/getSchedules.php) |
| `scheduleWorkflow` | `POST /v1/workflows/schedules` | `json` | [PHP](examples/workflows/schedules/scheduleWorkflow.php) |
| `getSchedule` | `GET /v1/workflows/schedules/{schedule_id}` | `none` | [PHP](examples/workflows/schedules/getSchedule.php) |
| `unscheduleWorkflow` | `DELETE /v1/workflows/schedules/{schedule_id}` | `none` | [PHP](examples/workflows/schedules/unscheduleWorkflow.php) |
| `updateSchedule` | `PATCH /v1/workflows/schedules/{schedule_id}` | `json` | [PHP](examples/workflows/schedules/updateSchedule.php) |
| `pauseSchedule` | `POST /v1/workflows/schedules/{schedule_id}/pause` | `json` | [PHP](examples/workflows/schedules/pauseSchedule.php) |
| `resumeSchedule` | `POST /v1/workflows/schedules/{schedule_id}/resume` | `json` | [PHP](examples/workflows/schedules/resumeSchedule.php) |
| `triggerSchedule` | `POST /v1/workflows/schedules/{schedule_id}/trigger` | `json` | [PHP](examples/workflows/schedules/triggerSchedule.php) |

### Workflows / Events

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `getStreamEvents` | `GET /v1/workflows/events/stream` | `none` | [PHP](examples/workflows/events/getStreamEvents.php) |
| `getWorkflowEvents` | `GET /v1/workflows/events/list` | `none` | [PHP](examples/workflows/events/getWorkflowEvents.php) |

### Workflows / Deployments

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listDeployments` | `GET /v1/workflows/deployments` | `none` | [PHP](examples/workflows/deployments/listDeployments.php) |
| `createDeployment` | `POST /v1/workflows/deployments` | `json` | [PHP](examples/workflows/deployments/createDeployment.php) |
| `updateDeployment` | `PATCH /v1/workflows/deployments/{name}` | `json` | [PHP](examples/workflows/deployments/updateDeployment.php) |
| `deleteDeployment` | `DELETE /v1/workflows/deployments/{name}` | `none` | [PHP](examples/workflows/deployments/deleteDeployment.php) |
| `getDeployment` | `GET /v1/workflows/deployments/{name}` | `none` | [PHP](examples/workflows/deployments/getDeployment.php) |
| `stopDeployment` | `POST /v1/workflows/deployments/{name}/stop` | `none` | [PHP](examples/workflows/deployments/stopDeployment.php) |
| `startDeployment` | `POST /v1/workflows/deployments/{name}/start` | `none` | [PHP](examples/workflows/deployments/startDeployment.php) |
| `restartDeployment` | `POST /v1/workflows/deployments/{name}/restart` | `none` | [PHP](examples/workflows/deployments/restartDeployment.php) |
| `listDeploymentWorkers` | `GET /v1/workflows/deployments/{name}/workers` | `none` | [PHP](examples/workflows/deployments/listDeploymentWorkers.php) |
| `getDeploymentLogs` | `GET /v1/workflows/deployments/{name}/logs` | `none` | [PHP](examples/workflows/deployments/getDeploymentLogs.php) |
| `streamDeploymentLogs` | `GET /v1/workflows/deployments/{name}/logs/stream` | `none` | [PHP](examples/workflows/deployments/streamDeploymentLogs.php) |

### Workflows

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `getWorkflows` | `GET /v1/workflows` | `none` | [PHP](examples/workflows/getWorkflows.php) |
| `getWorkflowRegistrations` | `GET /v1/workflows/registrations` | `none` | [PHP](examples/workflows/getWorkflowRegistrations.php) |
| `executeWorkflow` | `POST /v1/workflows/{workflow_identifier}/execute` | `json` | [PHP](examples/workflows/executeWorkflow.php) |
| `executeWorkflowRegistration` | `POST /v1/workflows/registrations/{workflow_registration_id}/execute` | `json` | [PHP](examples/workflows/executeWorkflowRegistration.php) |
| `getWorkflow` | `GET /v1/workflows/{workflow_identifier}` | `none` | [PHP](examples/workflows/getWorkflow.php) |
| `updateWorkflow` | `PUT /v1/workflows/{workflow_identifier}` | `json` | [PHP](examples/workflows/updateWorkflow.php) |
| `getWorkflowRegistration` | `GET /v1/workflows/registrations/{workflow_registration_id}` | `none` | [PHP](examples/workflows/getWorkflowRegistration.php) |
| `bulkArchiveWorkflows` | `PUT /v1/workflows/archive` | `json` | [PHP](examples/workflows/bulkArchiveWorkflows.php) |
| `bulkUnarchiveWorkflows` | `PUT /v1/workflows/unarchive` | `json` | [PHP](examples/workflows/bulkUnarchiveWorkflows.php) |
| `archiveWorkflow` | `PUT /v1/workflows/{workflow_identifier}/archive` | `none` | [PHP](examples/workflows/archiveWorkflow.php) |
| `unarchiveWorkflow` | `PUT /v1/workflows/{workflow_identifier}/unarchive` | `none` | [PHP](examples/workflows/unarchiveWorkflow.php) |

### Rag / Ingestion

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `getConfigs` | `GET /v1/rag/ingestion_pipeline_configurations` | `none` | [PHP](examples/rag/ingestion/getConfigs.php) |
| `registerConfig` | `PUT /v1/rag/ingestion_pipeline_configurations` | `json` | [PHP](examples/rag/ingestion/registerConfig.php) |
| `updateRunInfo` | `PUT /v1/rag/ingestion_pipeline_configurations/{id}/run_info` | `json` | [PHP](examples/rag/ingestion/updateRunInfo.php) |

### Rag / Search Indexes

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `getDeploymentSummaries` | `GET /v1/rag/deployments` | `none` | [PHP](examples/rag/search-indexes/getDeploymentSummaries.php) |
| `registerDeployment` | `PUT /v1/rag/deployments` | `json` | [PHP](examples/rag/search-indexes/registerDeployment.php) |
| `unregisterDeployment` | `DELETE /v1/rag/deployments/{deployment_id}` | `none` | [PHP](examples/rag/search-indexes/unregisterDeployment.php) |
| `updateIndexMetrics` | `PUT /v1/rag/deployments/{deployment_id}/metrics` | `json` | [PHP](examples/rag/search-indexes/updateIndexMetrics.php) |

### Users

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `getIdentity` | `GET /v1/users/me` | `none` | [PHP](examples/users/getIdentity.php) |
| `listOrganizations` | `GET /v1/users/me/organizations` | `none` | [PHP](examples/users/listOrganizations.php) |
| `listWorkspaces` | `GET /v1/users/me/workspaces` | `none` | [PHP](examples/users/listWorkspaces.php) |

### Administration / Users

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listAdminUsers` | `GET /v1/admin/users` | `none` | [PHP](examples/administration/users/listAdminUsers.php) |
| `createAdminUsers` | `POST /v1/admin/users` | `json` | [PHP](examples/administration/users/createAdminUsers.php) |
| `inviteAdminUsers` | `POST /v1/admin/users-invite` | `json` | [PHP](examples/administration/users/inviteAdminUsers.php) |
| `listAdminInvite` | `GET /v1/admin/users-invite` | `none` | [PHP](examples/administration/users/listAdminInvite.php) |
| `deleteAdminInvite` | `DELETE /v1/admin/users-invite/{invite_uuid}` | `none` | [PHP](examples/administration/users/deleteAdminInvite.php) |
| `updateAdminUser` | `PATCH /v1/admin/users/{user_id}` | `json` | [PHP](examples/administration/users/updateAdminUser.php) |
| `deleteAdminUser` | `DELETE /v1/admin/users/{user_id}` | `none` | [PHP](examples/administration/users/deleteAdminUser.php) |
| `retrieveAdminUser` | `GET /v1/admin/users/{user_id}` | `none` | [PHP](examples/administration/users/retrieveAdminUser.php) |
| `listAdminRoles` | `GET /v1/admin/roles` | `none` | [PHP](examples/administration/users/listAdminRoles.php) |

### Administration / Workspaces

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listAdminWorkspaces` | `GET /v1/admin/workspaces` | `none` | [PHP](examples/administration/workspaces/listAdminWorkspaces.php) |
| `createAdminWorkspace` | `POST /v1/admin/workspaces` | `json` | [PHP](examples/administration/workspaces/createAdminWorkspace.php) |
| `updateAdminWorkspaces` | `PATCH /v1/admin/workspaces/{workspace_uuid}` | `json` | [PHP](examples/administration/workspaces/updateAdminWorkspaces.php) |
| `deleteAdminWorkspaces` | `DELETE /v1/admin/workspaces/{workspace_uuid}` | `none` | [PHP](examples/administration/workspaces/deleteAdminWorkspaces.php) |
| `addAdminUsersWorkspaces` | `POST /v1/admin/workspaces/{workspace_uuid}/add-users` | `json` | [PHP](examples/administration/workspaces/addAdminUsersWorkspaces.php) |
| `addAdminOrUpdateUsersWorkspaces` | `PATCH /v1/admin/workspaces/{workspace_uuid}/users` | `json` | [PHP](examples/administration/workspaces/addAdminOrUpdateUsersWorkspaces.php) |
| `removeAdminUsersWorkspaces` | `DELETE /v1/admin/workspaces/{workspace_uuid}/remove-users` | `json` | [PHP](examples/administration/workspaces/removeAdminUsersWorkspaces.php) |

### Administration / Billing

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listAdminRateLimits` | `GET /v1/admin/rate-limit` | `none` | [PHP](examples/administration/billing/listAdminRateLimits.php) |
| `listAdminSpendLimits` | `GET /v1/admin/spend-limit` | `none` | [PHP](examples/administration/billing/listAdminSpendLimits.php) |
| `updateAdminSpendLimits` | `POST /v1/admin/spend-limit` | `json` | [PHP](examples/administration/billing/updateAdminSpendLimits.php) |
| `listAdminUsage` | `GET /v1/admin/usage` | `none` | [PHP](examples/administration/billing/listAdminUsage.php) |

### Administration / Audit Logs

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listAdminAuditLogs` | `GET /v1/admin/audit-logs` | `none` | [PHP](examples/administration/audit-logs/listAdminAuditLogs.php) |

### Administration / User Groups

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listAdminUserGroups` | `GET /v1/admin/user-groups` | `none` | [PHP](examples/administration/user-groups/listAdminUserGroups.php) |
| `createAdminUserGroup` | `POST /v1/admin/user-groups` | `json` | [PHP](examples/administration/user-groups/createAdminUserGroup.php) |
| `provisionAdminGroupToWorkspace` | `POST /v1/admin/user-groups/provision-workspace` | `json` | [PHP](examples/administration/user-groups/provisionAdminGroupToWorkspace.php) |
| `retrieveAdminUserGroup` | `GET /v1/admin/user-groups/{group_uuid}` | `none` | [PHP](examples/administration/user-groups/retrieveAdminUserGroup.php) |
| `updateAdminUserGroup` | `PATCH /v1/admin/user-groups/{group_uuid}` | `json` | [PHP](examples/administration/user-groups/updateAdminUserGroup.php) |
| `deleteAdminUserGroup` | `DELETE /v1/admin/user-groups/{group_uuid}` | `none` | [PHP](examples/administration/user-groups/deleteAdminUserGroup.php) |
| `retrieveAdminUserGroupMembers` | `GET /v1/admin/user-groups/{group_uuid}/members` | `none` | [PHP](examples/administration/user-groups/retrieveAdminUserGroupMembers.php) |
| `assignAdminUsersToGroup` | `POST /v1/admin/user-groups/{group_uuid}/members` | `json` | [PHP](examples/administration/user-groups/assignAdminUsersToGroup.php) |
| `removeAdminUsersFromGroup` | `DELETE /v1/admin/user-groups/{group_uuid}/members` | `json` | [PHP](examples/administration/user-groups/removeAdminUsersFromGroup.php) |
| `retrieveAdminGroupWorkspaceAssignments` | `GET /v1/admin/user-groups/{group_uuid}/workspaces` | `none` | [PHP](examples/administration/user-groups/retrieveAdminGroupWorkspaceAssignments.php) |
| `assignAdminGroupToWorkspace` | `POST /v1/admin/user-groups/{group_uuid}/workspaces` | `json` | [PHP](examples/administration/user-groups/assignAdminGroupToWorkspace.php) |
| `updateAdminGroupWorkspaceAssignment` | `PATCH /v1/admin/user-groups/{group_uuid}/workspaces/{workspace_uuid}` | `json` | [PHP](examples/administration/user-groups/updateAdminGroupWorkspaceAssignment.php) |
| `removeAdminGroupFromWorkspace` | `DELETE /v1/admin/user-groups/{group_uuid}/workspaces/{workspace_uuid}` | `none` | [PHP](examples/administration/user-groups/removeAdminGroupFromWorkspace.php) |
| `updateAdminUserGroupOrganizationRole` | `PATCH /v1/admin/user-groups/{group_uuid}/organization-role` | `json` | [PHP](examples/administration/user-groups/updateAdminUserGroupOrganizationRole.php) |
| `retrieveAdminNestedGroupsAdmin` | `GET /v1/admin/user-groups/{group_uuid}/nested` | `none` | [PHP](examples/administration/user-groups/retrieveAdminNestedGroupsAdmin.php) |
| `setAdminNestedGroupsAdmin` | `PATCH /v1/admin/user-groups/{group_uuid}/nested` | `json` | [PHP](examples/administration/user-groups/setAdminNestedGroupsAdmin.php) |

### Administration / Api Keys

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listAdminApiKeys` | `GET /v1/admin/api-keys` | `none` | [PHP](examples/administration/api-keys/listAdminApiKeys.php) |
| `createAdminApiKey` | `POST /v1/admin/api-keys` | `json` | [PHP](examples/administration/api-keys/createAdminApiKey.php) |
| `deleteAdminApiKey` | `DELETE /v1/admin/api-keys/{key_id}` | `none` | [PHP](examples/administration/api-keys/deleteAdminApiKey.php) |

### Administration / Scim

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `triggerAdminScimSync` | `POST /v1/admin/scim/sync` | `json` | [PHP](examples/administration/scim/triggerAdminScimSync.php) |
| `retrieveAdminScimSyncRun` | `GET /v1/admin/scim/sync/{run_id}` | `none` | [PHP](examples/administration/scim/retrieveAdminScimSyncRun.php) |

### Administration / Vibe Work Analytics

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `usageAdminByUser` | `GET /v1/admin/analytics/vibe/work/usage/by_user_stats` | `none` | [PHP](examples/administration/vibe-work-analytics/usageAdminByUser.php) |
| `usageAdminByAgent` | `GET /v1/admin/analytics/vibe/work/usage/by_agent_stats` | `none` | [PHP](examples/administration/vibe-work-analytics/usageAdminByAgent.php) |
| `usageAdminOverTime` | `GET /v1/admin/analytics/vibe/work/usage/by_time_stats` | `none` | [PHP](examples/administration/vibe-work-analytics/usageAdminOverTime.php) |

### Administration / Vibe Code Analytics

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `usageAdminByWorkspace` | `GET /v1/admin/analytics/vibe/code/usage/by_workspace` | `none` | [PHP](examples/administration/vibe-code-analytics/usageAdminByWorkspace.php) |
| `usageAdminByOrganization` | `GET /v1/admin/analytics/vibe/code/usage/by_organization` | `none` | [PHP](examples/administration/vibe-code-analytics/usageAdminByOrganization.php) |

<!-- ENDPOINT CATALOG END -->

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history and migration notes.

## License

This library is licensed under the ISC License. See [LICENSE.md](LICENSE.md).
