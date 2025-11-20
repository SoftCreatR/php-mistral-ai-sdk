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
 * Example: Submit an audio transcription request.
 *
 * If the bundled fixture is available, it is uploaded directly; otherwise, the
 * example falls back to using a remote URL.
 *
 * OpenAPI Reference:
 * - Operation ID: audio_api_v1_transcriptions_post
 */
$audioFixture = __DIR__ . '/fixtures/audio.mp3';

$payload = [
    'model' => 'voxtral-mini-latest',
    'language' => 'en',
];

if (\file_exists($audioFixture)) {
    $payload['file'] = $audioFixture;
} else {
    $payload['file_url'] = 'https://github.com/SoftCreatR/php-mistral-ai-sdk/raw/refs/heads/main/examples/audio/fixtures/audio.mp3';
}

MistralAIFactory::request('createAudioTranscription', $payload);
