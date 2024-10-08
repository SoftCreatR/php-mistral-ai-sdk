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
 * Example: Upload a file for fine-tuning purposes.
 *
 * Model Description:
 * Upload a file that contains document(s) to be used across various endpoints/features.
 * The file must be a valid JSON Lines (.jsonl) file, where each line is a JSON object
 * with the keys "prompt" and "completion".
 *
 * OpenAPI Specification Reference:
 * - Operation ID: upload_file_v1_files_post
 */
MistralAIFactory::request('uploadFile', [
    'file' => '/path/to/your/training_data.jsonl', // Replace with the actual file path
    'purpose' => 'fine-tune',
]);
