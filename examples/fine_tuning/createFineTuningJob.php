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
 * Example: Create a fine-tuning job.
 *
 * Model Description:
 * Creates a job that fine-tunes a specified model from a given dataset.
 * The response includes details of the enqueued job including job status and the name of the fine-tuned models once complete.
 *
 * In this example, we create a fine-tuning job using the 'open-mistral-7b' model.
 *
 * OpenAPI Specification Reference:
 * - Operation ID: create_fine_tuning_job_v1_fine_tuning_jobs_post
 */
MistralAIFactory::request('createFineTuningJob', [
    'model' => 'open-mistral-7b',
    'training_files' => [
        [
            'file_id' => 'file-abc123', // Replace with your actual training file ID
            'weight' => 1,
        ],
    ],
    'hyperparameters' => [
        'learning_rate' => 0.0001,
        'epochs' => 4,
    ],
    'suffix' => 'my-fine-tuned-model',
]);
