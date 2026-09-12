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

use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Utils;
use JsonException;
use PHPUnit\Framework\TestCase;
use SoftCreatR\MistralAI\Exception\MistralAIException;
use SoftCreatR\MistralAI\Http\ServerSentEventDecoder;

/**
 * @covers \SoftCreatR\MistralAI\Http\ServerSentEventDecoder
 */
final class ServerSentEventDecoderTest extends TestCase
{
    /**
     * @throws MistralAIException
     */
    public function testDecodesFramesWithCommentsMixedLineEndingsAndMultilineData(): void
    {
        $stream = Utils::streamFor(
            ": keep-alive\r\nevent: response.output_text.delta\r\n"
            . "data: {\"id\": 1,\r\ndata: \"ok\": true}\r\n\r\n"
            . "data: {\"id\": 2}\n\n",
        );
        $events = [];

        (new ServerSentEventDecoder())->decode($stream, static function (mixed $event) use (&$events): void {
            $events[] = $event;
        });

        $this->assertSame([
            ['id' => 1, 'ok' => true],
            ['id' => 2],
        ], $events);
    }

    /**
     * @throws MistralAIException
     */
    public function testHandlesArbitraryChunkingAndStopsAtDoneFrame(): void
    {
        $source = Utils::streamFor(
            "\xEF\xBB\xBFdata: {\"delta\": \"x\"}\r\n\r\n"
            . "data: [DONE]\r\n\r\n"
            . "data: {\"ignored\": true}\r\n\r\n",
        );
        $stream = FnStream::decorate($source, [
            'read' => static fn(int $length): string => $source->read(1),
        ]);
        $events = [];

        (new ServerSentEventDecoder())->decode($stream, static function (mixed $event) use (&$events): void {
            $events[] = $event;
        });

        $this->assertSame([['delta' => 'x']], $events);
        $this->assertFalse($source->eof());
    }

    /**
     * @throws MistralAIException
     */
    public function testDispatchesFinalUnterminatedFrame(): void
    {
        $events = [];

        (new ServerSentEventDecoder())->decode(
            Utils::streamFor('data: {"final": true}'),
            static function (mixed $event) use (&$events): void {
                $events[] = $event;
            },
        );

        $this->assertSame([['final' => true]], $events);
    }

    /**
     * @throws MistralAIException
     */
    public function testIgnoresColonlessNonDataFields(): void
    {
        $events = [];

        (new ServerSentEventDecoder())->decode(
            Utils::streamFor("event\ndata: {\"id\": 1}\n\n"),
            static function (mixed $event) use (&$events): void {
                $events[] = $event;
            },
        );

        $this->assertSame([['id' => 1]], $events);
    }

    /**
     * @throws MistralAIException
     */
    public function testRetriesWhenANonBlockingStreamTemporarilyReturnsNoData(): void
    {
        $source = Utils::streamFor("data: {\"id\": 1}\n\n");
        $readCount = 0;
        $stream = FnStream::decorate($source, [
            'read' => static function (int $length) use ($source, &$readCount): string {
                if ($readCount++ === 0) {
                    return '';
                }

                return $source->read($length);
            },
        ]);
        $events = [];

        (new ServerSentEventDecoder())->decode($stream, static function (mixed $event) use (&$events): void {
            $events[] = $event;
        });

        $this->assertSame([['id' => 1]], $events);
    }

    /**
     * @throws MistralAIException
     */
    public function testConsumesAllReportedUnreadBytes(): void
    {
        $source = Utils::streamFor("data: {\"id\": 1}\n\n");
        $stream = FnStream::decorate($source, [
            'getMetadata' => static function (?string $key = null) use ($source): mixed {
                if ($key === 'unread_bytes') {
                    return $source->getSize() - $source->tell();
                }

                return $source->getMetadata($key);
            },
        ]);
        $events = [];

        (new ServerSentEventDecoder())->decode($stream, static function (mixed $event) use (&$events): void {
            $events[] = $event;
        });

        $this->assertSame([['id' => 1]], $events);
    }

    /**
     * @throws MistralAIException
     */
    public function testStopsAtDoneFrameBufferedUntilEndOfStream(): void
    {
        (new ServerSentEventDecoder())->decode(
            Utils::streamFor("data: [DONE]\r\r"),
            fn() => $this->fail('The done frame must not be passed to the callback.'),
        );

        $this->addToAssertionCount(1);
    }

    public function testWrapsJsonDecodeErrors(): void
    {
        try {
            (new ServerSentEventDecoder())->decode(Utils::streamFor("data: {invalid}\n\n"), static function (): void {});
            $this->fail('Expected invalid SSE JSON to throw.');
        } catch (MistralAIException $exception) {
            $this->assertStringStartsWith('JSON decode error:', $exception->getMessage());
            $this->assertInstanceOf(JsonException::class, $exception->getPrevious());
        }
    }

    /**
     * @throws MistralAIException
     */
    public function testDoesNotWrapJsonExceptionsThrownByTheCallback(): void
    {
        $callbackException = new JsonException('Callback failed.');

        $this->expectExceptionObject($callbackException);

        (new ServerSentEventDecoder())->decode(
            Utils::streamFor("data: {\"valid\": true}\n\n"),
            static fn() => throw $callbackException,
        );
    }
}
