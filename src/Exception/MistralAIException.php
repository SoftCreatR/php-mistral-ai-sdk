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

namespace SoftCreatR\MistralAI\Exception;

use Exception;
use JsonException;
use Throwable;

use const JSON_THROW_ON_ERROR;

/**
 * Exception class for handling errors in the Mistral AI API client.
 *
 * This exception is thrown when the Mistral AI API client encounters an error.
 * It attempts to extract a meaningful error message from the API response,
 * which may be in JSON format containing an "error" object.
 */
class MistralAIException extends Exception
{
    /** @var array<string, mixed>|null */
    private ?array $error = null;

    /** @var array<string, string[]> */
    private array $responseHeaders;

    private ?string $responseBody;

    private ?string $requestId;

    /**
     * Constructs a new MistralAIException instance.
     *
     * @param string|null    $message  The exception message. If it's a valid JSON string containing an "error.message",
     *                                 that message will be used instead.
     * @param int            $code     The exception code.
     * @param Throwable|null $previous The previous exception used for exception chaining.
     * @param string|null $requestId The Mistral AI request identifier, when available.
     * @param array<string, string[]> $responseHeaders Response headers, when available.
     */
    public function __construct(
        ?string $message,
        int $code = 0,
        ?Throwable $previous = null,
        ?string $requestId = null,
        array $responseHeaders = [],
    ) {
        $this->responseBody = $message;
        $this->requestId = $requestId !== '' ? $requestId : null;
        $this->responseHeaders = $responseHeaders;

        if (empty($message)) {
            $message = 'An unknown error occurred';
        } else {
            $message = $this->extractErrorMessageFromJson($message);
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getError(): ?array
    {
        return $this->error;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    /**
     * @return array<string, string[]>
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * Attempts to extract the error message from a JSON-encoded string.
     *
     * If the provided error message is a JSON string containing an "error.message",
     * this method will extract and return that message. Otherwise, it returns the original message.
     *
     * @param string $errorMessage The error message, potentially encoded as a JSON string.
     *
     * @return string The extracted error message, or the original message if extraction fails.
     */
    private function extractErrorMessageFromJson(string $errorMessage): string
    {
        try {
            $decoded = \json_decode($errorMessage, true, 512, JSON_THROW_ON_ERROR);

            if (isset($decoded['error']) && \is_array($decoded['error'])) {
                $this->error = $decoded['error'];

                if (isset($decoded['error']['message']) && \is_string($decoded['error']['message'])) {
                    return $decoded['error']['message'];
                }
            }
        } catch (JsonException) {
            // Ignore JSON decoding errors and return the original message
        }

        return $errorMessage;
    }
}
