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

use Closure;
use InvalidArgumentException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

final class MultipartBodyBuilder
{
    /** @var Closure(string, string): mixed */
    private readonly Closure $resourceFactory;

    /**
     * @param StreamFactoryInterface $streamFactory
     * @param (callable(string, string): mixed)|null $resourceFactory
     */
    public function __construct(
        private readonly StreamFactoryInterface $streamFactory,
        ?callable $resourceFactory = null,
    ) {
        $this->resourceFactory = $resourceFactory === null
            ? static fn(string $path, string $mode) => @\fopen($path, $mode)
            : $resourceFactory(...);
    }

    /**
     * @param array<string|int, mixed> $fields
     * @param array<int, mixed> $fileFields
     *
     * @throws Throwable
     */
    public function build(array $fields, string $boundary, array $fileFields): StreamInterface
    {
        $this->validateBoundary($boundary);

        $resource = ($this->resourceFactory)('php://temp', 'w+b');

        if (!\is_resource($resource)) {
            throw new RuntimeException('Unable to create a temporary multipart stream.');
        }

        try {
            $normalizedFileFields = $this->normalizeFileFields($fileFields);

            foreach ($fields as $name => $value) {
                $fieldName = (string) $name;
                $this->writeValue(
                    $resource,
                    $boundary,
                    $fieldName,
                    $value,
                    $normalizedFileFields,
                    isset($normalizedFileFields[$fieldName]),
                );
            }

            $this->writeAll($resource, "--{$boundary}--\r\n");

            if (!@\rewind($resource)) {
                throw new RuntimeException('Unable to rewind the multipart stream.');
            }

            return $this->streamFactory->createStreamFromResource($resource);
        } catch (Throwable $exception) {
            \fclose($resource);

            throw $exception;
        }
    }

    private function validateBoundary(string $boundary): void
    {
        if (!\preg_match("/^[0-9A-Za-z'()+_,.\\/:=?-]{1,70}$/D", $boundary)) {
            throw new InvalidArgumentException('The multipart boundary is invalid.');
        }
    }

    /**
     * @param array<int, mixed> $fileFields
     *
     * @return array<string, true>
     */
    private function normalizeFileFields(array $fileFields): array
    {
        $normalized = [];

        foreach ($fileFields as $fileField) {
            if (!\is_string($fileField) || $fileField === '') {
                throw new InvalidArgumentException('Multipart file field names must be non-empty strings.');
            }

            $normalized[$fileField] = true;
        }

        return $normalized;
    }

    /**
     * @param resource $resource
     * @param string $boundary
     * @param string $name
     * @param mixed $value
     * @param array<string, true> $fileFields
     * @param bool $isFile
     */
    private function writeValue(
        $resource,
        string $boundary,
        string $name,
        mixed $value,
        array $fileFields,
        bool $isFile,
    ): void {
        if (\is_array($value)) {
            foreach ($value as $key => $nestedValue) {
                $nestedName = \is_int($key)
                    ? $name . '[]'
                    : $name . '[' . $key . ']';

                $this->writeValue(
                    $resource,
                    $boundary,
                    $nestedName,
                    $nestedValue,
                    $fileFields,
                    $isFile || isset($fileFields[$nestedName]),
                );
            }

            return;
        }

        if ($isFile || isset($fileFields[$name])) {
            $this->writeFile($resource, $boundary, $name, $value);

            return;
        }

        $this->writeField($resource, $boundary, $name, $value);
    }

    /**
     * @param resource $resource
     */
    private function writeField($resource, string $boundary, string $name, mixed $value): void
    {
        if ($value === null) {
            $contents = '';
        } elseif (\is_bool($value)) {
            $contents = $value ? 'true' : 'false';
        } elseif (\is_scalar($value)) {
            $contents = (string) $value;
        } else {
            throw new InvalidArgumentException("Multipart field '{$name}' must contain a scalar, null, or array value.");
        }

        $escapedName = $this->escapeQuotedString($name);
        $this->writeAll($resource, "--{$boundary}\r\n");
        $this->writeAll($resource, "Content-Disposition: form-data; name=\"{$escapedName}\"\r\n\r\n");
        $this->writeAll($resource, $contents);
        $this->writeAll($resource, "\r\n");
    }

    /**
     * @param resource $resource
     */
    private function writeFile($resource, string $boundary, string $name, mixed $path): void
    {
        if (!\is_string($path) || $path === '' || !\is_file($path) || !\is_readable($path)) {
            throw new InvalidArgumentException("Multipart file field '{$name}' must reference a readable file path.");
        }

        $file = ($this->resourceFactory)($path, 'rb');

        if (!\is_resource($file)) {
            throw new InvalidArgumentException("Unable to open the file for multipart field '{$name}'.");
        }

        try {
            $filename = \basename($path);
            $escapedName = $this->escapeQuotedString($name);
            $escapedFilename = $this->escapeQuotedString($filename);
            $mimeType = $this->inferMimeType($path);

            $this->writeAll($resource, "--{$boundary}\r\n");
            $this->writeAll(
                $resource,
                "Content-Disposition: form-data; name=\"{$escapedName}\"; filename=\"{$escapedFilename}\"\r\n",
            );
            $this->writeAll($resource, "Content-Type: {$mimeType}\r\n\r\n");

            if (@\stream_copy_to_stream($file, $resource) === false) {
                throw new RuntimeException("Unable to read the file for multipart field '{$name}'.");
            }

            $this->writeAll($resource, "\r\n");
        } finally {
            \fclose($file);
        }
    }

    private function inferMimeType(string $path): string
    {
        if (\function_exists('mime_content_type')) {
            $mimeType = @\mime_content_type($path);

            if (\is_string($mimeType) && $mimeType !== '' && !\str_contains($mimeType, "\r")
                && !\str_contains($mimeType, "\n")) {
                return $mimeType;
            }
        }

        return 'application/octet-stream';
    }

    private function escapeQuotedString(string $value): string
    {
        $value = \str_replace(["\r", "\n"], ['%0D', '%0A'], $value);

        return \addcslashes($value, "\\\"");
    }

    /**
     * @param resource $resource
     */
    private function writeAll($resource, string $contents): void
    {
        $offset = 0;
        $length = \strlen($contents);

        while ($offset < $length) {
            $written = @\fwrite($resource, \substr($contents, $offset));

            if ($written === false || $written === 0) {
                throw new RuntimeException('Unable to write to the multipart stream.');
            }

            $offset += $written;
        }
    }
}
