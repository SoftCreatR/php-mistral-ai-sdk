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

require_once __DIR__ . '/../../vendor/autoload.php';

use GuzzleHttp\Psr7\Response;
use SoftCreatR\JsonPayloadContract\Contract;
use SoftCreatR\JsonPayloadContract\Field;
use SoftCreatR\MistralAI\Response\JsonResponse;

if (!class_exists(Contract::class)) {
    throw new RuntimeException(
        'Install softcreatr/json-payload-contract:^1.0 to run this optional example (PHP 8.3 or newer).',
    );
}

final readonly class SupportSummaryDto
{
    public function __construct(
        public string $responseId,
        public string $summary,
        public string $sentiment,
    ) {}
}

$body = file_get_contents(__DIR__ . '/fixtures/response.json');

if ($body === false) {
    throw new RuntimeException('Unable to read the response fixture.');
}

$response = JsonResponse::fromResponse(new Response(200, ['x-request-id' => 'req_example'], $body));

// The business contract rejects truncated completions before reading the generated JSON.
$envelopeContract = Contract::define([
    'responseId' => Field::required('$.id')->string(),
    'finishReason' => Field::required('$.choices[0].finish_reason')->string()->oneOf(['stop']),
    'text' => Field::required('$.choices[0].message.content')->string()->nonEmpty(),
]);
$summaryContract = Contract::define([
    'summary' => Field::required('$.summary')->string()->nonEmpty(),
    'sentiment' => Field::required('$.sentiment')->string()->oneOf(['positive', 'neutral', 'negative']),
]);

$envelope = $response->mapJson($envelopeContract->applyJson(...));
$summary = $summaryContract->applyJson($envelope['text']);
$dto = new SupportSummaryDto($envelope['responseId'], $summary['summary'], $summary['sentiment']);

echo json_encode($dto, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
