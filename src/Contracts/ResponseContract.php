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

namespace SoftCreatR\MistralAI\Contracts;

use JsonSerializable;
use Psr\Http\Message\ResponseInterface;

/**
 * Common access to an explicitly decoded JSON response and its HTTP metadata.
 */
interface ResponseContract extends JsonSerializable
{
    public function getResponse(): ResponseInterface;

    public function getRawBody(): string;

    public function getRequestId(): ?string;

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array;

    /**
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array;
}
