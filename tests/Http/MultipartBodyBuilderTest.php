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

namespace SoftCreatR\MistralAI\Tests\Http;

use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;
use SoftCreatR\MistralAI\Http\MultipartBodyBuilder;
use SoftCreatR\MistralAI\Tests\TestHelper;
use stdClass;
use Throwable;

final class UnseekableWritableStreamWrapper
{
    public mixed $context = null;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return true;
    }

    public function stream_write(string $data): int
    {
        return \strlen($data);
    }

    public function stream_tell(): int
    {
        return 0;
    }

    public function stream_eof(): bool
    {
        return false;
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return false;
    }

    /** @return array<string, int> */
    public function stream_stat(): array
    {
        return [];
    }
}

final class UnreadableStreamWrapper
{
    public mixed $context = null;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return true;
    }

    public function stream_read(int $count): bool
    {
        return false;
    }

    public function stream_eof(): bool
    {
        return false;
    }

    /** @return array<string, int> */
    public function stream_stat(): array
    {
        return [];
    }
}

/**
 * @covers \SoftCreatR\MistralAI\Http\MultipartBodyBuilder
 */
final class MultipartBodyBuilderTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function testBuildsScalarAndNestedFieldsUsingMultipartFormConventions(): void
    {
        $stream = (new MultipartBodyBuilder(new HttpFactory()))->build([
            'purpose' => 'assistants',
            'enabled' => true,
            'disabled' => false,
            'nullable' => null,
            'tags' => ['first', 'second'],
            'metadata' => ['quoted"key\\part' => 42],
        ], 'MistralAI-test-boundary', []);

        $body = $stream->getContents();

        $this->assertStringContainsString("name=\"purpose\"\r\n\r\nassistants\r\n", $body);
        $this->assertStringContainsString("name=\"enabled\"\r\n\r\ntrue\r\n", $body);
        $this->assertStringContainsString("name=\"disabled\"\r\n\r\nfalse\r\n", $body);
        $this->assertStringContainsString("name=\"nullable\"\r\n\r\n\r\n", $body);
        $this->assertSame(2, \substr_count($body, 'name="tags[]"'));
        $this->assertStringContainsString('name="metadata[quoted\\"key\\\\part]"', $body);
        $this->assertStringEndsWith("--MistralAI-test-boundary--\r\n", $body);
        $this->assertSame('php://temp', $stream->getMetadata('uri'));
    }

    /**
     * @throws Throwable
     */
    public function testWritesEndpointDefinedFileFieldsAsRawRepeatedParts(): void
    {
        $firstPath = \tempnam(\sys_get_temp_dir(), 'mistral-multipart-');
        $secondPath = \tempnam(\sys_get_temp_dir(), 'mistral-multipart-');
        $this->assertNotFalse($firstPath);
        $this->assertNotFalse($secondPath);

        $firstContents = "raw\x00\xFFupload";
        $secondContents = 'second-file';
        \file_put_contents($firstPath, $firstContents);
        \file_put_contents($secondPath, $secondContents);

        try {
            $stream = (new MultipartBodyBuilder(new HttpFactory()))->build([
                'payloads' => [$firstPath, $secondPath],
            ], 'MistralAI-file-boundary', ['payloads']);
            $body = $stream->getContents();

            $this->assertSame(2, \substr_count($body, 'name="payloads[]"; filename="'));
            $this->assertStringContainsString("Content-Type: ", $body);
            $this->assertStringContainsString($firstContents, $body);
            $this->assertStringContainsString($secondContents, $body);
            $this->assertStringNotContainsString(\base64_encode($firstContents), $body);
        } finally {
            @\unlink($firstPath);
            @\unlink($secondPath);
        }
    }

    /**
     * @throws Throwable
     */
    public function testRejectsUnreadableFilePath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must reference a readable file path');

        (new MultipartBodyBuilder(new HttpFactory()))->build([
            'custom_upload' => __DIR__ . '/missing-file',
        ], 'MistralAI-test-boundary', ['custom_upload']);
    }

    /**
     * @throws Throwable
     */
    public function testRejectsInvalidBoundary(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('boundary is invalid');

        (new MultipartBodyBuilder(new HttpFactory()))->build([], "boundary\r\nInjected: true", []);
    }

    /**
     * @throws Throwable
     */
    public function testRejectsInvalidFileFieldName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('file field names must be non-empty strings');

        (new MultipartBodyBuilder(new HttpFactory()))->build([], 'MistralAI-test-boundary', [null]);
    }

    /**
     * @throws Throwable
     */
    public function testRejectsUnsupportedFieldValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Multipart field 'metadata'");

        (new MultipartBodyBuilder(new HttpFactory()))->build([
            'metadata' => new stdClass(),
        ], 'MistralAI-test-boundary', []);
    }

    /**
     * @throws Throwable
     */
    public function testRejectsFailedTemporaryStreamCreation(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to create a temporary multipart stream');

        (new MultipartBodyBuilder(
            new HttpFactory(),
            static fn(string $path, string $mode): bool => false,
        ))->build([], 'MistralAI-test-boundary', []);
    }

    /**
     * @throws Throwable
     */
    public function testRejectsTemporaryStreamThatCannotBeRewound(): void
    {
        $scheme = 'mistral-unseekable';
        $this->assertTrue(\stream_wrapper_register($scheme, UnseekableWritableStreamWrapper::class));

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Unable to rewind the multipart stream');

            (new MultipartBodyBuilder(
                new HttpFactory(),
                static fn(string $path, string $mode) => \fopen("{$scheme}://stream", $mode),
            ))->build(['field' => 'value'], 'MistralAI-test-boundary', []);
        } finally {
            \stream_wrapper_unregister($scheme);
        }
    }

    /**
     * @throws Throwable
     */
    public function testRejectsFileThatCannotBeOpenedAfterValidation(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'mistral-multipart-');
        $this->assertNotFalse($path);

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Unable to open the file');

            (new MultipartBodyBuilder(
                new HttpFactory(),
                static fn(string $resourcePath, string $mode) => $mode === 'rb'
                    ? false
                    : \fopen($resourcePath, $mode),
            ))->build(['file' => $path], 'MistralAI-test-boundary', ['file']);
        } finally {
            @\unlink($path);
        }
    }

    /**
     * @throws Throwable
     */
    public function testRejectsFileThatCannotBeCopied(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'mistral-multipart-');
        $this->assertNotFalse($path);
        $scheme = 'mistral-unreadable';
        $this->assertTrue(\stream_wrapper_register($scheme, UnreadableStreamWrapper::class));

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Unable to read the file');

            (new MultipartBodyBuilder(
                new HttpFactory(),
                static fn(string $resourcePath, string $mode) => $mode === 'rb'
                    ? \fopen("{$scheme}://stream", $mode)
                    : \fopen($resourcePath, $mode),
            ))->build(['file' => $path], 'MistralAI-test-boundary', ['file']);
        } finally {
            \stream_wrapper_unregister($scheme);
            @\unlink($path);
        }
    }

    /**
     * @throws ReflectionException
     */
    public function testFallsBackToBinaryMimeType(): void
    {
        $builder = new MultipartBodyBuilder(new HttpFactory());
        $method = TestHelper::getPrivateMethod($builder, 'inferMimeType');

        $this->assertSame('application/octet-stream', $method->invoke($builder, __DIR__ . '/missing-file'));
    }

    /**
     * @throws ReflectionException
     */
    public function testRejectsFailedStreamWrite(): void
    {
        $resource = \fopen('php://memory', 'rb');
        $this->assertIsResource($resource);

        try {
            $builder = new MultipartBodyBuilder(new HttpFactory());
            $method = TestHelper::getPrivateMethod($builder, 'writeAll');

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Unable to write to the multipart stream');
            $method->invoke($builder, $resource, 'contents');
        } finally {
            \fclose($resource);
        }
    }
}
