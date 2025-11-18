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

namespace SoftCreatR\MistralAI\Tests;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;

class TestHelper
{
    /**
     * Retrieves a private or protected method from a class instance or class name.
     *
     * @param object|string $objectOrClass The object instance or class name.
     * @param string        $methodName    The name of the method to access.
     *
     * @return ReflectionMethod The reflection method instance, set to be accessible.
     *
     * @throws ReflectionException If the method does not exist.
     */
    public static function getPrivateMethod(object|string $objectOrClass, string $methodName): ReflectionMethod
    {
        return (new ReflectionClass($objectOrClass))->getMethod($methodName);
    }

    /**
     * Retrieves the private constructor of a class using reflection.
     *
     * @param string $className The fully qualified class name.
     *
     * @return ReflectionMethod The reflection method instance representing the constructor.
     *
     * @throws ReflectionException If the class or constructor does not exist.
     */
    public static function getPrivateConstructor(string $className): ReflectionMethod
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            return $constructor;
        }

        throw new ReflectionException("Constructor does not exist for class {$className}.");
    }

    /**
     * Loads the contents of a response file for testing purposes.
     *
     * @param string $filename The filename to load from the 'fixtures/responses' directory.
     *
     * @return string The contents of the response file.
     *
     * @throws RuntimeException If the file cannot be found or read.
     */
    public static function loadResponseFromFile(string $filename): string
    {
        $filePath = __DIR__ . '/fixtures/responses/' . $filename;

        if (!\file_exists($filePath)) {
            throw new RuntimeException("Response file not found: {$filePath}");
        }

        $content = \file_get_contents($filePath);

        if ($content === false) {
            throw new RuntimeException("Unable to read response file: {$filePath}");
        }

        return $content;
    }
}
