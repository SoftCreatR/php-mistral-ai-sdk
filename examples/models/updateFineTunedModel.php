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
 * Example: Update a fine-tuned model's metadata.
 *
 * Model Description:
 * Update the name or description of a fine-tuned model.
 *
 * In this example, we update the name and description of a fine-tuned model.
 *
 * OpenAPI Specification Reference:
 * - Operation ID: update_ft_model_v1_fine_tuning_models__model_id__patch
 */
MistralAIFactory::request('updateFineTunedModel', ['model_id' => 'ft:open-mistral-7b:my-great-model:abc123'], [
    'name' => 'Updated Fine-Tuned Model Name',
    'description' => 'This is an updated description for the fine-tuned model.',
]);
