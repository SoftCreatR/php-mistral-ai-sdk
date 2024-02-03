# Mistral API Wrapper for PHP

[![Build](https://img.shields.io/github/actions/workflow/status/SoftCreatR/php-mistral-ai-sdk/.github/workflows/create-release.yml?branch=main)](https://github.com/SoftCreatR/php-mistral-ai-sdk/actions/workflows/create-release.yml) [![Latest Release](https://img.shields.io/packagist/v/SoftCreatR/php-mistral-ai-sdk?color=blue&label=Latest%20Release)](https://packagist.org/packages/softcreatr/php-mistral-ai-sdk) [![ISC licensed](https://img.shields.io/badge/license-ISC-blue.svg)](./LICENSE.md) [![Plant Tree](https://img.shields.io/badge/dynamic/json?color=brightgreen&label=Plant%20Tree&query=%24.total&url=https%3A%2F%2Fpublic.offset.earth%2Fusers%2Fsoftcreatr%2Ftrees)](https://ecologi.com/softcreatr?r=61212ab3fc69b8eb8a2014f4) [![Codecov branch](https://img.shields.io/codecov/c/github/SoftCreatR/php-mistral-ai-sdk)](https://codecov.io/gh/SoftCreatR/php-mistral-ai-sdk) [![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability-percentage/SoftCreatR/php-mistral-ai-sdk)](https://codeclimate.com/github/SoftCreatR/php-mistral-ai-sdk)

This PHP library provides a simple wrapper for the Mistral API, allowing you to easily integrate the Mistral API into your PHP projects.


## Features

-   Easy integration with Mistral API
-   Supports all Mistral API endpoints
-   Utilizes PSR-17 and PSR-18 compliant HTTP clients, and factories for making API requests

## Requirements

-   PHP 7.4 or higher
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

Now you can call any supported MistralAI API endpoint using the magic method `__call`:

```php
$response = $mistral->createChatCompletion([
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

For more details on how to use each endpoint, refer to the [Mistral API documentation](https://docs.mistral.ai), and the [examples](https://github.com/SoftCreatR/php-mistral-ai-sdk/tree/main/examples) provided in the repository.

## Supported Methods

### Chat Completions
-   [Create Chat Completion](https://docs.mistral.ai/api/#operation/createChatCompletion) - [Example](https://github.com/SoftCreatR/php-mistral-ai-sdk/blob/main/examples/chat/createChatCompletion.php)
    -   `createChatCompletion(array $options = [])`

### Embeddings
-   [Create Chat Completion](https://docs.mistral.ai/api/#operation/createEmbedding) - [Example](https://github.com/SoftCreatR/php-mistral-ai-sdk/blob/main/examples/embeddings/createEmbedding.php)
    -   `createEmbedding(array $options = [])`

### Models
-   [List Models](https://docs.mistral.ai/api/#operation/listModels) - [Example](https://github.com/SoftCreatR/php-mistral-ai-sdk/blob/main/examples/models/listModels.php)
    -   `listModels()`

## Changelog

For a detailed list of changes and updates, please refer to the [CHANGELOG.md](https://github.com/SoftCreatR/php-mistral-ai-sdk/blob/main/CHANGELOG.md) file. We adhere to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) and document notable changes for each release.

## Known Problems and limitations

### Streaming Support
Currently, streaming is not supported. It's planned to address this limitation asap. For now, please be aware that these methods cannot be used for streaming purposes.

If you require streaming functionality, consider using an alternative implementation or keep an eye out for future updates to this library.

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
