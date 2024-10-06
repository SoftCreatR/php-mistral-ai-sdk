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
 * Example: Create a chat completion using the 'mistral-small-latest' model with streaming enabled.
 *
 * Model Description:
 * ID of the model to use. You can use the List Available Models API to see all of your available models,
 * or see the Model Overview for model descriptions.
 *
 * In this example, we use 'mistral-small-latest' as the model.
 *
 * OpenAPI Specification Reference:
 * - Operation ID: create_chat_completion_v1_chat_completions_post
 */
MistralAIFactory::request('createChatCompletion', [
    'model' => 'mistral-small-latest',
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Tell me a story about a brave knight.',
        ],
    ],
    'stream' => true,
], static function ($data) {
    if (isset($data['choices'][0]['delta']['content'])) {
        echo $data['choices'][0]['delta']['content'];
    }
});
