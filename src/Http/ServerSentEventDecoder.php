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

namespace SoftCreatR\MistralAI\Http;

use JsonException;
use Psr\Http\Message\StreamInterface;
use SoftCreatR\MistralAI\Exception\MistralAIException;

use const JSON_THROW_ON_ERROR;

final class ServerSentEventDecoder
{
    private const CHUNK_SIZE = 8_192;

    /**
     * @param callable(mixed): void $callback
     *
     * @throws MistralAIException If an event contains invalid JSON.
     */
    public function decode(StreamInterface $stream, callable $callback): void
    {
        $buffer = '';
        $dataLines = [];
        $firstLine = true;

        $dispatch = function () use (&$dataLines, $callback): bool {
            return $this->dispatch($dataLines, $callback);
        };

        $consumeLine = static function (string $line) use (&$dataLines, &$firstLine, $dispatch): bool {
            if ($firstLine) {
                $firstLine = false;

                if (\str_starts_with($line, "\xEF\xBB\xBF")) {
                    $line = \substr($line, 3);
                }
            }

            if ($line === '') {
                return $dispatch();
            }

            if ($line[0] === ':') {
                return false;
            }

            $colon = \strpos($line, ':');

            if ($colon === false) {
                $field = $line;
                $value = '';
            } else {
                $field = \substr($line, 0, $colon);
                $value = \substr($line, $colon + 1);

                if (\str_starts_with($value, ' ')) {
                    $value = \substr($value, 1);
                }
            }

            if ($field === 'data') {
                $dataLines[] = $value;
            }

            return false;
        };

        while (!$stream->eof()) {
            $chunk = $stream->read($this->getReadLength($stream));

            if ($chunk === '') {
                // PSR-7 permits non-blocking streams to make no progress before EOF.
                \usleep(1_000);

                continue;
            }

            $buffer .= $chunk;

            if ($this->consumeCompleteLines($buffer, false, $consumeLine)) {
                return;
            }
        }

        if ($this->consumeCompleteLines($buffer, true, $consumeLine)) {
            return;
        }

        if ($buffer !== '') {
            $consumeLine($buffer);
        }

        $dispatch();
    }

    private function getReadLength(StreamInterface $stream): int
    {
        $unreadBytes = $stream->getMetadata('unread_bytes');

        if (\is_int($unreadBytes) && $unreadBytes > 0) {
            return \min($unreadBytes, self::CHUNK_SIZE);
        }

        // A streaming HTTP socket may only have a small SSE frame available.
        // Reading one byte waits for that frame without waiting for an arbitrary
        // large buffer to fill; subsequent reads consume all reported bytes.
        return 1;
    }

    /**
     * @param list<string> $dataLines
     * @param callable(mixed): void $callback
     *
     * @throws MistralAIException
     */
    private function dispatch(array &$dataLines, callable $callback): bool
    {
        if ($dataLines === []) {
            return false;
        }

        $data = \implode("\n", $dataLines);
        $dataLines = [];

        if (\trim($data) === '[DONE]') {
            return true;
        }

        try {
            $decoded = \json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new MistralAIException('JSON decode error: ' . $exception->getMessage(), 0, $exception);
        }

        $callback($decoded);

        return false;
    }

    /**
     * @param callable(string): bool $consumeLine
     */
    private function consumeCompleteLines(string &$buffer, bool $atEndOfStream, callable $consumeLine): bool
    {
        while ($buffer !== '') {
            $carriageReturn = \strpos($buffer, "\r");
            $lineFeed = \strpos($buffer, "\n");

            if ($carriageReturn === false && $lineFeed === false) {
                return false;
            }

            if ($carriageReturn === false) {
                $lineEnd = $lineFeed;
            } elseif ($lineFeed === false) {
                $lineEnd = $carriageReturn;
            } else {
                $lineEnd = \min($carriageReturn, $lineFeed);
            }

            if (!$atEndOfStream && $buffer[$lineEnd] === "\r" && $lineEnd === \strlen($buffer) - 1) {
                return false;
            }

            $delimiterLength = $buffer[$lineEnd] === "\r" && ($buffer[$lineEnd + 1] ?? null) === "\n" ? 2 : 1;
            $line = \substr($buffer, 0, $lineEnd);
            $buffer = \substr($buffer, $lineEnd + $delimiterLength);

            if ($consumeLine($line)) {
                return true;
            }
        }

        return false;
    }
}
