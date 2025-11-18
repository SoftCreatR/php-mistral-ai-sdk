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

require_once __DIR__ . '/../MistralAIFactory.php';

/**
 * Example: Submit an OCR extraction job.
 *
 * OpenAPI Reference:
 * - Operation ID: ocr_v1_ocr_post
 */
$pdfFixture = __DIR__ . '/fixtures/document.pdf';

$payload = [
    'model' => 'mistral-ocr-latest',
];

if (\file_exists($pdfFixture)) {
    $base64 = \base64_encode(\file_get_contents($pdfFixture));
    $payload['document'] = [
        'type' => 'document_url',
        'document_url' => 'data:application/pdf;base64,' . $base64,
    ];
} else {
    $payload['document'] = [
        'type' => 'document_url',
        'document_url' => 'https://example.com/sample.pdf',
    ];
}

MistralAIFactory::request('createOcr', $payload);
