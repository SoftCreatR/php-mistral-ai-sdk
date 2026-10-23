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

use Error;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use SoftCreatR\MistralAI\Response\ChatCompletionResponse;
use SoftCreatR\MistralAI\Response\JsonResponse;

#[CoversClass(JsonResponse::class)]
#[CoversClass(ChatCompletionResponse::class)]
final class ChatCompletionResponseTest extends TestCase
{
    /** @throws JsonException */
    public function testPreservesThePayloadAndHttpMetadata(): void
    {
        $data = self::payload();
        $raw = \json_encode($data, JSON_THROW_ON_ERROR);
        $response = new Response(200, ['x-request-id' => 'req_example'], $raw);
        $dto = ChatCompletionResponse::fromResponse($response);

        $this->assertSame('cmpl_example', $dto->id);
        $this->assertSame('mistral-small-latest', $dto->model);
        $this->assertSame($data['choices'], $dto->choices);
        $this->assertSame($data['usage'], $dto->usage);
        $this->assertSame('Hello.', $dto->getOutputText());
        $this->assertSame('stop', $dto->getFinishReason());
        $this->assertSame($response, $dto->getResponse());
        $this->assertSame($raw, $dto->getRawBody());
        $this->assertSame('req_example', $dto->getRequestId());
        $this->assertSame($data, $dto->toArray());
        $this->assertSame($raw, \json_encode($dto, JSON_THROW_ON_ERROR));
    }

    /** @throws JsonException */
    public function testReadsTextChunksWithoutMixingAlternativesOrReasoning(): void
    {
        $dto = self::wrap(['choices' => [
            ['message' => ['content' => [
                ['type' => 'thinking', 'thinking' => 'Ignore.'],
                ['type' => 'text', 'text' => 'Hello'],
                ['type' => 'future', 'text' => 'Ignore.'],
                null,
                ['type' => 'text', 'text' => ' world.'],
            ]]],
            ['message' => ['content' => 'Alternative.'], 'finish_reason' => 'future_reason'],
        ]]);

        $this->assertSame('Hello world.', $dto->getOutputText());
        $this->assertSame('Alternative.', $dto->getOutputText(1));
        $this->assertSame('future_reason', $dto->getFinishReason(1));
        $this->assertNull($dto->getFinishReason());
        $this->assertSame('', $dto->getOutputText(2));
        $this->assertNull($dto->getFinishReason(2));
    }

    /** @throws JsonException */
    public function testAcceptsEmptyChoicesAndMissingOptionalUsageOrContent(): void
    {
        $data = self::payload();
        unset($data['usage']);
        $data['choices'] = [];
        $dto = ChatCompletionResponse::fromResponse(new Response(200, [], \json_encode($data, JSON_THROW_ON_ERROR)));
        $this->assertNull($dto->usage);
        $this->assertSame('', $dto->getOutputText());
        $this->assertSame('', self::wrap(['choices' => [['message' => ['content' => null]]]])->getOutputText());
        $this->assertSame('', self::wrap(['choices' => [['message' => []]]])->getOutputText());
        $this->assertNull(self::wrap(['usage' => null])->usage);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @throws JsonException
     */
    #[DataProvider('invalidEnvelopes')]
    public function testRejectsInvalidEnvelopes(array $overrides): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::wrap($overrides);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidEnvelopes(): iterable
    {
        yield 'missing id' => [['id' => null]];
        yield 'invalid id' => [['id' => 1]];
        yield 'missing model' => [['model' => null]];
        yield 'invalid model' => [['model' => 1]];
        yield 'wrong object' => [['object' => 'list']];
        yield 'missing choices' => [['choices' => null]];
        yield 'scalar choices' => [['choices' => 'invalid']];
        yield 'non-list choices' => [['choices' => ['first' => []]]];
        yield 'invalid usage' => [['usage' => 'invalid']];
    }

    /**
     * @param list<mixed> $choices
     *
     * @throws JsonException
     */
    #[DataProvider('invalidChoices')]
    public function testRejectsInvalidRecognizedChoiceValues(array $choices, string $method, int $index = 0): void
    {
        $dto = self::wrap(['choices' => $choices]);
        $this->expectException(InvalidArgumentException::class);
        $dto->{$method}($index);
    }

    /** @return iterable<string, array{0:list<mixed>, 1:string, 2?:int}> */
    public static function invalidChoices(): iterable
    {
        yield 'negative index' => [[], 'getOutputText', -1];
        yield 'scalar choice' => [['invalid'], 'getOutputText'];
        yield 'scalar message' => [[['message' => 'invalid']], 'getOutputText'];
        yield 'scalar content' => [[['message' => ['content' => true]]], 'getOutputText'];
        yield 'missing text' => [[['message' => ['content' => [['type' => 'text']]]]], 'getOutputText'];
        yield 'invalid text' => [[['message' => ['content' => [['type' => 'text', 'text' => 1]]]]], 'getOutputText'];
        yield 'invalid finish reason' => [[['finish_reason' => 1]], 'getFinishReason'];
    }

    /**
     * @throws ReflectionException
     * @throws JsonException
     */
    public function testClassAndPropertiesAreReadOnly(): void
    {
        $this->assertTrue((new ReflectionClass(ChatCompletionResponse::class))->isReadOnly());
        $dto = self::wrap([]);
        $this->expectException(Error::class);
        (new ReflectionProperty($dto, 'id'))->setValue($dto, 'changed');
    }

    /** @return array<string, mixed> */
    private static function payload(): array
    {
        return [
            'id' => 'cmpl_example',
            'object' => 'chat.completion',
            'model' => 'mistral-small-latest',
            'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => 'Hello.'], 'finish_reason' => 'stop']],
            'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2, 'future_metric' => 1],
            'future_field' => true,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @throws JsonException
     */
    private static function wrap(array $overrides): ChatCompletionResponse
    {
        $data = \array_replace(self::payload(), $overrides);
        $response = new Response(200, [], \json_encode($data, JSON_THROW_ON_ERROR));

        return ChatCompletionResponse::fromJsonResponse(JsonResponse::fromResponse($response));
    }
}
