<?php

/*
 * Copyright (c) 2024, Sascha Greuel and Contributors
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
 * Example: Create an audio transcription.
 *
 * Model Description:
 * Transcribes audio files into text using Mistral's audio transcription model.
 * Supports various audio formats (mp3, wav, m4a, etc.) and optional parameters
 * like language specification and temperature for controlling the output.
 *
 * OpenAPI Specification Reference:
 * - Operation ID: create_transcription_v1_audio_transcriptions_post
 * - Endpoint: POST /v1/audio/transcriptions
 */
MistralAIFactory::request('createAudioTranscription', [
    'file' => '/path/to/your/audio.mp3', // Replace with the actual audio file path
    'model' => 'mistral-whisper',
    'language' => 'en', // Optional: Language of the audio (e.g., 'en', 'fr', 'es')
    'temperature' => 0.0, // Optional: Temperature for controlling randomness (0.0 - 1.0)
]);
