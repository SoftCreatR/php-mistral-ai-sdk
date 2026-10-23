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

namespace SoftCreatR\MistralAI\Tests\Response;

use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use SoftCreatR\MistralAI\Response\JsonResponse;
use stdClass;
use Throwable;

#[CoversClass(JsonResponse::class)]
final class JsonResponseTest extends TestCase
{
    /** @throws ReflectionException */
    public function testClassIsReadOnly(): void
    {
        $this->assertTrue((new ReflectionClass(JsonResponse::class))->isReadOnly());
    }

    /**
     * @throws JsonException
     */
    public function testSnapshotsSeekableStreamsAndPreservesHttpMetadata(): void
    {
        $raw = '{"object":"list","data":[],"future":{"enabled":true}}';
        $response = new Response(200, [
            'Content-Type' => 'application/json',
            'x-request-id' => 'req_123',
            'x-ratelimit-remaining-requests' => '20',
        ], $raw);
        $response->getBody()->seek(9);

        $json = JsonResponse::fromResponse($response);

        $this->assertSame($response, $json->getResponse());
        $this->assertSame(9, $response->getBody()->tell());
        $this->assertSame($raw, $json->getRawBody());
        $this->assertSame('req_123', $json->getRequestId());
        $this->assertSame('20', $json->getResponse()->getHeaderLine('x-ratelimit-remaining-requests'));
        $this->assertSame([
            'object' => 'list',
            'data' => [],
            'future' => ['enabled' => true],
        ], $json->toArray());
        $this->assertSame($json->toArray(), $json->jsonSerialize());
        $this->assertSame($raw, \json_encode($json, JSON_THROW_ON_ERROR));
    }

    /**
     * @throws JsonException
     */
    public function testCapturesNonSeekableStreamsWithoutLosingTheSnapshot(): void
    {
        $stream = new NoSeekStream(Utils::streamFor('{"ok":true}'));
        $response = new Response(200, [], $stream);
        $json = JsonResponse::fromResponse($response);

        $this->assertTrue($stream->eof());
        $this->assertSame('{"ok":true}', $json->getRawBody());
        $this->assertSame(['ok' => true], $json->toArray());
        $this->assertSame($response, $json->getResponse());
        $this->assertNull($json->getRequestId());
    }

    /**
     * @throws JsonException
     */
    public function testCanWrapPreviouslyCapturedJsonWithoutReadingTheStream(): void
    {
        $response = new Response(200, [], '{"unused":true}');
        $response->getBody()->seek(5);

        $json = JsonResponse::fromJson('{"integer":9223372036854775808}', $response);
        $this->assertSame(['integer' => '9223372036854775808'], $json->toArray());
        $this->assertSame(5, $response->getBody()->tell());

        $emptyObject = JsonResponse::fromJson('{}', $response);
        $emptyArray = JsonResponse::fromJson('[]', $response);
        $this->assertSame([], $emptyObject->toArray());
        $this->assertSame([], $emptyArray->toArray());
        $this->assertSame('{}', $emptyObject->getRawBody());
        $this->assertSame('[]', $emptyArray->getRawBody());
    }

    /**
     * @throws JsonException
     * @throws Throwable
     */
    public function testMapsDataAndRawJsonWithoutChangingStoredData(): void
    {
        $raw = '{"value":1,"value":2,"empty":{}}';
        $json = JsonResponse::fromJson($raw, new Response());

        $mapped = $json->map(static function (array $data): stdClass {
            $dto = new stdClass();
            $dto->value = $data['value'];
            $data['value'] = 3;

            return $dto;
        });

        $this->assertSame(2, $mapped->value);
        $this->assertSame(2, $json->toArray()['value']);
        $this->assertSame($raw, $json->mapJson(static fn(string $body): string => $body));
        $this->assertSame($raw, $json->getRawBody());
    }

    /**
     * @throws JsonException
     * @throws Throwable
     */
    public function testMapperExceptionsPropagate(): void
    {
        $json = JsonResponse::fromJson('{}', new Response());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/' . \preg_quote('Mapper failed.', '/') . '/');

        $json->map(static fn(array $data) => throw new RuntimeException('Mapper failed.'));
    }

    /**
     * @throws JsonException
     * @throws Throwable
     */
    public function testRawJsonMapperExceptionsPropagate(): void
    {
        $json = JsonResponse::fromJson('{}', new Response());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/' . \preg_quote('Contract failed.', '/') . '/');

        $json->mapJson(static fn(string $body) => throw new RuntimeException('Contract failed.'));
    }

    public function testRestoresStreamPositionWhenJsonDecodingFails(): void
    {
        $response = new Response(200, [], '{invalid');
        $response->getBody()->seek(3);

        try {
            JsonResponse::fromResponse($response);
            $this->fail('Invalid JSON should throw.');
        } catch (JsonException) {
            $this->assertSame(3, $response->getBody()->tell());
        }
    }

    /**
     * @throws JsonException
     */
    public function testRestoresStreamPositionWhenReadingFails(): void
    {
        $source = Utils::streamFor('{"ok":true}');
        $source->seek(3);
        $stream = FnStream::decorate($source, [
            'getContents' => static fn() => throw new RuntimeException('Unreadable stream.'),
        ]);

        try {
            JsonResponse::fromResponse(new Response(200, [], $stream));
            $this->fail('A stream read failure should throw.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Unreadable stream.', $exception->getMessage());
            $this->assertSame(3, $source->tell());
        }
    }

    /**
     * @throws JsonException
     */
    #[DataProvider('scalarRoots')]
    public function testRejectsScalarJsonRoots(string $raw): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/' . \preg_quote('A JSON response must contain an object or array.', '/') . '/');

        JsonResponse::fromJson($raw, new Response());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function scalarRoots(): iterable
    {
        yield 'null' => ['null'];
        yield 'boolean' => ['true'];
        yield 'number' => ['42'];
        yield 'string' => ['"text"'];
    }

    public function testRejectsInvalidJsonFromANonSeekableStream(): void
    {
        $this->expectException(JsonException::class);

        JsonResponse::fromResponse(new Response(200, [], new NoSeekStream(Utils::streamFor('invalid'))));
    }
}
