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
use Throwable;

use const JSON_BIGINT_AS_STRING;
use const JSON_THROW_ON_ERROR;

/**
 * Opt-in JSON decoding and mapping that retains the original payload and response.
 */
final readonly class JsonResponse implements ResponseContract
{
    /**
     * @param array<array-key, mixed> $data
     */
    private function __construct(
        private ResponseInterface $response,
        private string $rawBody,
        private array $data,
    ) {}

    /**
     * Snapshots the complete body, restoring its position when it is seekable.
     * Non-seekable bodies are consumed and must be wrapped before being read.
     *
     * @throws JsonException If the body is invalid JSON.
     * @throws InvalidArgumentException If the JSON root is not an object or array.
     * @throws RuntimeException If the response stream cannot be read or repositioned.
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        $stream = $response->getBody();

        if (!$stream->isSeekable()) {
            return self::fromJson($stream->getContents(), $response);
        }

        $position = $stream->tell();

        try {
            $stream->rewind();
            $rawBody = $stream->getContents();
        } finally {
            $stream->seek($position);
        }

        return self::fromJson($rawBody, $response);
    }

    /**
     * Wraps a body already captured by the caller without reading the HTTP stream.
     *
     * @throws JsonException If the body is invalid JSON.
     * @throws InvalidArgumentException If the JSON root is not an object or array.
     */
    public static function fromJson(string $rawBody, ResponseInterface $response): self
    {
        $data = \json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);

        if (!\is_array($data)) {
            throw new InvalidArgumentException('A JSON response must contain an object or array.');
        }

        return new self($response, $rawBody, $data);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    public function getRequestId(): ?string
    {
        $requestId = $this->response->getHeaderLine('x-request-id');

        return $requestId === '' ? null : $requestId;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @template T
     *
     * @param callable(array<array-key, mixed>):T $mapper
     *
     * @return T
     *
     * @throws Throwable If the mapper fails.
     */
    public function map(callable $mapper): mixed
    {
        return $mapper($this->data);
    }

    /**
     * Applies a raw-JSON mapper, such as JSONPayloadContract's applyJson().
     * The original JSON preserves object/array distinctions and duplicate members.
     *
     * @template T
     *
     * @param callable(string):T $mapper
     *
     * @return T
     *
     * @throws Throwable If the mapper fails.
     */
    public function mapJson(callable $mapper): mixed
    {
        return $mapper($this->rawBody);
    }
}
