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

namespace SoftCreatR\MistralAI\Exception;

use Exception;
use Throwable;

use const JSON_THROW_ON_ERROR;

/**
 * Represents an exception thrown by the MistralAI API client.
 */
class MistralAIException extends Exception
{
    /**
     * Creates a new MistralAIException instance.
     *
     * @param string $message The exception message. If it's a valid JSON string, the "error.message" value will be used.
     * @param int $code The exception code.
     * @param Throwable|null $previous The previous exception used for the exception chaining.
     */
    public function __construct(
        string $message = "An unknown error occurred",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($this->extractErrorMessageFromJson($message), $code, $previous);
    }

    /**
     * Extracts the error message from a JSON-encoded string, if available.
     *
     * @param string $errorMessage The error message, encoded as a JSON string.
     *
     * @return string The extracted error message, or the original error message if JSON decoding failed,
     *                or the error message does not contain the expected structure.
     */
    private function extractErrorMessageFromJson(string $errorMessage): string
    {
        try {
            $decoded = \json_decode($errorMessage, true, 512, JSON_THROW_ON_ERROR);

            if (\is_array($decoded) && isset($decoded['error']['message'])) {
                return $decoded['error']['message'];
            }
        } catch (Exception $e) {
            // ignore
        }

        return $errorMessage;
    }
}
