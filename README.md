# Mistral API Wrapper for PHP

[![Build](https://img.shields.io/github/actions/workflow/status/SoftCreatR/php-mistral-ai-sdk/.github/workflows/create-release.yml?branch=main)](https://github.com/SoftCreatR/php-mistral-ai-sdk/actions/workflows/create-release.yml) [![Latest Release](https://img.shields.io/packagist/v/SoftCreatR/php-mistral-ai-sdk?color=blue&label=Latest%20Release)](https://packagist.org/packages/softcreatr/php-mistral-ai-sdk) [![ISC licensed](https://img.shields.io/badge/license-ISC-blue.svg)](./LICENSE.md) [![Plant Tree](https://img.shields.io/badge/dynamic/json?color=brightgreen&label=Plant%20Tree&query=%24.total&url=https%3A%2F%2Fpublic.ecologi.com%2Fusers%2Fsoftcreatr%2Ftrees)](https://ecologi.com/softcreatr?r=61212ab3fc69b8eb8a2014f4) [![Codecov branch](https://img.shields.io/codecov/c/github/SoftCreatR/php-mistral-ai-sdk)](https://codecov.io/gh/SoftCreatR/php-mistral-ai-sdk) [![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability-percentage/SoftCreatR/php-mistral-ai-sdk)](https://codeclimate.com/github/SoftCreatR/php-mistral-ai-sdk)

This PHP library provides a simple wrapper for the Mistral API, allowing you to easily integrate the Mistral API into your PHP projects.

## Features

-   Easy integration with Mistral API
-   Supports all Mistral API endpoints
-   Streaming support for real-time responses in chat completions
-   Utilizes PSR-17 and PSR-18 compliant HTTP clients and factories for making API requests

## Requirements

-   PHP 8.1 or higher
-   A PSR-17 HTTP Factory implementation (e.g., [guzzle/psr7](https://github.com/guzzle/psr7) or [nyholm/psr7](https://github.com/Nyholm/psr7))
-   A PSR-18 HTTP Client implementation (e.g., [guzzlehttp/guzzle](https://github.com/guzzle/guzzle) or [symfony/http-client](https://github.com/symfony/http-client))

## Installation

You can install the library via [Composer](https://getcomposer.org/):

```bash
composer require softcreatr/php-mistral-ai-sdk
```

## Usage

First, include the library in your project:

```php
<?php

require_once 'vendor/autoload.php';
```

Then, create an instance of the `MistralAI` class with your API key, organization (optional), an HTTP client, an HTTP request factory, and an HTTP stream factory:

```php
use SoftCreatR\MistralAI\MistralAI;

$apiKey = 'your_api_key';

// Replace these lines with your chosen PSR-17 and PSR-18 compatible HTTP client and factories
$httpClient = new YourChosenHttpClient();
$requestFactory = new YourChosenRequestFactory();
$streamFactory = new YourChosenStreamFactory();
$uriFactory = new YourChosenUriFactory();

$mistral = new MistralAI($requestFactory, $streamFactory, $uriFactory, $httpClient, $apiKey);
```

Now you can call any supported MistralAI API endpoint using the magic method `__call`.
The first array argument is reserved for URL/path parameters. If an endpoint has
no placeholders you can either provide your request payload directly as the
first argument or use the optional second `$options` array. The examples below
use the `$options` argument to make the intent explicit:

```php
$response = $mistral->createChatCompletion([], [
    'model' => 'mistral-tiny',
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Who is the most renowned French painter?'
        ],
    ],
]);

// Process the API response
if ($response->getStatusCode() === 200) {
    $responseObj = json_decode($response->getBody()->getContents(), true);
    
    print_r($responseObj);
} else {
    echo "Error: " . $response->getStatusCode();
}
```

### Streaming Example

You can enable real-time streaming for chat completions:

```php
$streamCallback = static function ($data) {
    if (isset($data['choices'][0]['delta']['content'])) {
        echo $data['choices'][0]['delta']['content'];
    }
};

$mistral->createChatCompletion([], [
    'model' => 'mistral-small-latest',
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Tell me a story about a brave knight.',
        ],
    ],
    'stream' => true,
], $streamCallback);
```

When an endpoint requires query-string parameters in addition to a JSON body
(for example, promoting an agent to a specific version), include them via the
reserved `query` key inside the `$options` array: `['query' => ['version' => '2.0.0']]`.

For more details on how to use each endpoint, refer to the [Mistral API documentation](https://docs.mistral.ai), and the [examples](https://github.com/SoftCreatR/php-mistral-ai-sdk/tree/main/examples) provided in the repository.

### Example Coverage

Every documented endpoint ships with a runnable script under `examples/`. Newly added directories include `examples/audio`, `examples/batch`, `examples/agents_beta`, `examples/conversations`, `examples/libraries` (and `examples/libraries/documents`), `examples/classifiers`, and `examples/ocr`, covering the latest transcription, batch, agents (beta), conversations (beta), knowledge libraries, moderation/classification, and OCR APIs.

## Supported Methods

### Chat Completions
-   [Create Chat Completion](https://docs.mistral.ai/api/endpoint/chat) – [Example](https://github.com/SoftCreatR/php-mistral-ai-sdk/blob/main/examples/chat/createChatCompletion.php)
    -   `createChatCompletion(array $parameters = [], array $options = [], ?callable $streamCallback = null)`

### Audio Transcriptions
-   [Create Audio Transcription](https://docs.mistral.ai/api/endpoint/audio/transcriptions)
    -   `createAudioTranscription(array $parameters = [], array $options = [], ?callable $streamCallback = null)`
-   [Stream Audio Transcription](https://docs.mistral.ai/api/endpoint/audio/transcriptions)
    -   `createAudioTranscriptionStream(array $parameters = [], array $options = [], ?callable $streamCallback = null)`

### Embeddings
-   [Create Embedding](https://docs.mistral.ai/api/endpoint/embeddings) – [Example](https://github.com/SoftCreatR/php-mistral-ai-sdk/blob/main/examples/embeddings/createEmbedding.php)
    -   `createEmbedding(array $parameters = [], array $options = [])`

### Models
-   [List Models](https://docs.mistral.ai/api/endpoint/models)
    -   `listModels(array $parameters = [], array $options = [])`
-   [Retrieve / Delete Model](https://docs.mistral.ai/api/endpoint/models)
    -   `retrieveModel(array $parameters = [], array $options = [])`
    -   `deleteModel(array $parameters = [], array $options = [])`
-   [Fine-tuned Model Lifecycle](https://docs.mistral.ai/api/endpoint/models)
    -   `updateFineTunedModel(array $parameters = [], array $options = [])`
    -   `archiveModel(array $parameters = [], array $options = [])`
    -   `unarchiveModel(array $parameters = [], array $options = [])`

### Batch Jobs
-   [Batch Job APIs](https://docs.mistral.ai/api/endpoint/batch)
    -   `listBatchJobs()`, `createBatchJob(array $parameters = [], array $options = [])`
    -   `retrieveBatchJob(array $parameters = [])`, `cancelBatchJob(array $parameters = [])`

### Files
-   [File Management](https://docs.mistral.ai/api/endpoint/files) – [Examples](https://github.com/SoftCreatR/php-mistral-ai-sdk/tree/main/examples/files)
    -   `uploadFile(array $parameters = [], array $options = [])`
    -   `listFiles(array $parameters = [], array $options = [])`
    -   `retrieveFile(array $parameters = [], array $options = [])`
    -   `deleteFile(array $parameters = [], array $options = [])`
    -   `downloadFile(array $parameters = [], array $options = [])`
    -   `retrieveFileSignedUrl(array $parameters = [], array $options = [])`

### Fine-Tuning Jobs
-   [Fine-Tuning Jobs](https://docs.mistral.ai/api/endpoint/fine-tuning) – [Examples](https://github.com/SoftCreatR/php-mistral-ai-sdk/tree/main/examples/fine_tuning)
    -   `listFineTuningJobs()`, `retrieveFineTuningJob(array $parameters = [])`
    -   `createFineTuningJob(array $parameters = [], array $options = [])`
    -   `cancelFineTuningJob(array $parameters = [])`, `startFineTuningJob(array $parameters = [])`

### FIM Completion
-   [Create FIM Completion](https://docs.mistral.ai/api/endpoint/fim) – [Example](https://github.com/SoftCreatR/php-mistral-ai-sdk/blob/main/examples/fim/createFimCompletion.php)
    -   `createFimCompletion(array $parameters = [], array $options = [], ?callable $streamCallback = null)`

### Agents Completion
-   [Create Agents Completion](https://docs.mistral.ai/api/endpoint/agents) – [Example](https://github.com/SoftCreatR/php-mistral-ai-sdk/blob/main/examples/agents/createAgentsCompletion.php)
    -   `createAgentsCompletion(array $parameters = [], array $options = [], ?callable $streamCallback = null)`

### Agents API (Beta)
-   [Manage Agents](https://docs.mistral.ai/api/endpoint/beta/agents)
    -   `listAgents()`, `createAgent(array $parameters = [], array $options = [])`
    -   `retrieveAgent(array $parameters = [])`, `updateAgent(array $parameters = [], array $options = [])`
    -   `updateAgentVersion(array $parameters = [], array $options = [])`, `deleteAgent(array $parameters = [])`

### Conversations API (Beta)
-   [Conversational Workflows](https://docs.mistral.ai/api/endpoint/beta/conversations)
    -   `listConversations()`, `startConversation(array $parameters = [], array $options = [], ?callable $streamCallback = null)`
    -   `retrieveConversation(array $parameters = [])`, `appendConversation(array $parameters = [], array $options = [], ?callable $streamCallback = null)`
    -   `deleteConversation(array $parameters = [])`, `restartConversation(array $parameters = [], array $options = [], ?callable $streamCallback = null)`
    -   Streaming helpers: `startConversationStream`, `appendConversationStream`, `restartConversationStream`
    -   History helpers: `listConversationHistory(array $parameters = [])`, `listConversationMessages(array $parameters = [])`

### Knowledge Libraries (Beta)
-   [Libraries & Shares](https://docs.mistral.ai/api/endpoint/beta/libraries)
    -   `listLibraries()`, `createLibrary(array $parameters = [], array $options = [])`
    -   `retrieveLibrary(array $parameters = [])`, `updateLibrary(array $parameters = [], array $options = [])`
    -   `deleteLibrary(array $parameters = [])`, `listLibraryShares(array $parameters = [])`
    -   `upsertLibraryShare(array $parameters = [], array $options = [])`, `deleteLibraryShare(array $parameters = [])`
-   [Library Documents](https://docs.mistral.ai/api/endpoint/beta/libraries/documents)
    -   `listLibraryDocuments(array $parameters = [])`, `uploadLibraryDocument(array $parameters = [], array $options = [])`
    -   `retrieveLibraryDocument(array $parameters = [])`, `updateLibraryDocument(array $parameters = [], array $options = [])`
    -   `deleteLibraryDocument(array $parameters = [])`, `retrieveLibraryDocumentStatus(array $parameters = [])`
    -   `retrieveLibraryDocumentTextContent(array $parameters = [])`
    -   `retrieveLibraryDocumentSignedUrl(array $parameters = [])`
    -   `retrieveLibraryDocumentExtractedTextSignedUrl(array $parameters = [])`
    -   `reprocessLibraryDocument(array $parameters = [], array $options = [])`

### Moderations & Classifications
-   [Safety & Classifier APIs](https://docs.mistral.ai/api/endpoint/classifiers)
    -   `createModeration(array $parameters = [], array $options = [])`
    -   `createChatModeration(array $parameters = [], array $options = [])`
    -   `createClassification(array $parameters = [], array $options = [])`
    -   `createChatClassification(array $parameters = [], array $options = [])`

### OCR
-   [Create OCR Job](https://docs.mistral.ai/api/endpoint/ocr)
    -   `createOcr(array $parameters = [], array $options = [])`

## Changelog

For a detailed list of changes and updates, please refer to the [CHANGELOG.md](https://github.com/SoftCreatR/php-mistral-ai-sdk/blob/main/CHANGELOG.md) file. We adhere to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) and document notable changes for each release.

## Known Problems and Limitations

### Streaming Support
Streaming is now supported for real-time token generation in chat completions. Please make sure you are handling streams correctly using a callback, as demonstrated in the examples.

## License

This library is licensed under the ISC License. See the [LICENSE](https://github.com/SoftCreatR/php-mistral-ai-sdk/blob/main/LICENSE.md) file for more information.

## Maintainers 🛠️

<table>
<tr>
    <td style="text-align:center;word-wrap:break-word;width:150px;height: 150px">
        <a href=https://github.com/SoftCreatR>
            <img src=https://avatars.githubusercontent.com/u/81188?v=4 width="100;" alt="Sascha Greuel"/>
            <br />
            <sub style="font-size:14px"><b>Sascha Greuel</b></sub>
        </a>
    </td>
</tr>
</table>

## Contributors ✨

<table>
<tr>
</tr>
</table>
