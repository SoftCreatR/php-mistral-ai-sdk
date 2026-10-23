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

namespace SoftCreatR\MistralAI\Response;

use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use SoftCreatR\MistralAI\Contracts\ResponseContract;

/**
 * Optional DTO for chat completions, preserving unknown fields and content types.
 */
final readonly class ChatCompletionResponse implements ResponseContract
{
    public string $id;

    public string $model;

    /** @var list<mixed> */
    public array $choices;

    /** @var array<array-key, mixed>|null */
    public ?array $usage;

    /** @throws InvalidArgumentException If a common response field has an invalid type. */
    private function __construct(private ResponseContract $response)
    {
        $data = $response->toArray();

        foreach (['id', 'model'] as $field) {
            if (!isset($data[$field]) || !\is_string($data[$field])) {
                throw new InvalidArgumentException("Response field \"{$field}\" must be a string.");
            }
        }

        if (($data['object'] ?? null) !== 'chat.completion') {
            throw new InvalidArgumentException('Expected a chat completion response.');
        }

        if (!isset($data['choices']) || !\is_array($data['choices']) || !\array_is_list($data['choices'])) {
            throw new InvalidArgumentException('Response field "choices" must be a list.');
        }

        $usage = $data['usage'] ?? null;

        if ($usage !== null && !\is_array($usage)) {
            throw new InvalidArgumentException('Response field "usage" must be an object or null.');
        }

        $this->id = $data['id'];
        $this->model = $data['model'];
        $this->choices = $data['choices'];
        $this->usage = $usage;
    }

    /**
     * @throws JsonException If the body is invalid JSON.
     * @throws InvalidArgumentException If common response fields have invalid types.
     * @throws RuntimeException If the response stream cannot be read or repositioned.
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        return self::fromJsonResponse(JsonResponse::fromResponse($response));
    }

    /** @throws InvalidArgumentException If common response fields have invalid types. */
    public static function fromJsonResponse(ResponseContract $response): self
    {
        return new self($response);
    }

    /**
     * Reads one alternative, excluding reasoning and unknown content types.
     *
     * @throws InvalidArgumentException If a recognized choice or text value has an invalid type.
     */
    public function getOutputText(int $choiceIndex = 0): string
    {
        $choice = $this->getChoice($choiceIndex);
        $message = $choice['message'] ?? [];

        if (!\is_array($message)) {
            throw new InvalidArgumentException('Choice field "message" must be an object.');
        }

        $content = $message['content'] ?? null;

        if ($content === null) {
            return '';
        }

        if (\is_string($content)) {
            return $content;
        }

        if (!\is_array($content)) {
            throw new InvalidArgumentException('Message content must be a string, array, or null.');
        }

        $text = [];

        foreach ($content as $part) {
            if (!\is_array($part) || ($part['type'] ?? null) !== 'text') {
                continue;
            }

            if (!isset($part['text']) || !\is_string($part['text'])) {
                throw new InvalidArgumentException('Text content must contain a string "text" value.');
            }

            $text[] = $part['text'];
        }

        return \implode('', $text);
    }

    /** @throws InvalidArgumentException If the choice or finish reason has an invalid type. */
    public function getFinishReason(int $choiceIndex = 0): ?string
    {
        $reason = $this->getChoice($choiceIndex)['finish_reason'] ?? null;

        if ($reason !== null && !\is_string($reason)) {
            throw new InvalidArgumentException('Choice field "finish_reason" must be a string or null.');
        }

        return $reason;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response->getResponse();
    }

    public function getRawBody(): string
    {
        return $this->response->getRawBody();
    }

    public function getRequestId(): ?string
    {
        return $this->response->getRequestId();
    }

    /** @return array<array-key, mixed> */
    public function toArray(): array
    {
        return $this->response->toArray();
    }

    /** @return array<array-key, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws InvalidArgumentException If the choice index or value has an invalid type.
     */
    private function getChoice(int $choiceIndex): array
    {
        if ($choiceIndex < 0) {
            throw new InvalidArgumentException('Choice index must not be negative.');
        }

        $choice = $this->choices[$choiceIndex] ?? [];

        if (!\is_array($choice)) {
            throw new InvalidArgumentException('Response choices must contain objects.');
        }

        return $choice;
    }
}
