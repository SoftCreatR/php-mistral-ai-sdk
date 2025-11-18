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
 * Example: Create a Fill-in-the-Middle (FIM) completion using the 'codestral-2405' model.
 *
 * Model Description:
 * ID of the model to use. Only compatible for now with:
 *   - 'codestral-2405'
 *   - 'codestral-latest'
 *
 * In this example, we use 'codestral-2405' as the model.
 *
 * OpenAPI Specification Reference:
 * - Operation ID: create_fim_completion_v1_fim_completions_post
 */
MistralAIFactory::request('createFimCompletion', [
    'model' => 'codestral-2405',
    'prompt' => 'def',
    'suffix' => 'return a + b',
    'temperature' => 0.7,
    'top_p' => 1,
]);
