<?php

declare(strict_types=1);

/*
 * This file is part of the "sbuerk/composer-files-provider" composer plugin.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace SBUERK\ComposerFilesProvider\Tests\Unit\Replacer;

use SBUERK\ComposerFilesProvider\Replacer\EnvPlaceholderReplacer;
use SBUERK\ComposerFilesProvider\Tests\Unit\TestCase;

class EnvPlaceholderReplacerTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    protected $originalEnvVars = [];

    protected function tearDown(): void
    {
        $this->restoreEnvVars();
        parent::tearDown();
    }

    public function envPlaceholderReplacerReplacesWithExpectedValueDataProvider(): \Generator
    {
        yield 'string placeholder without default - set returns env value' => [
            'variabel' => '%env(string:envName)%',
            [
                'envName' => 'dummyEnvValue',
            ],
            'dummyEnvValue',
        ];

        yield 'string placeholder without default - not set returns empty result' => [
            'variabel' => '%env(string:envName)%',
            [],
            '',
        ];

        yield 'string placeholder with default - set returns env value' => [
            'variabel' => '%env(string:envName:defaultEnvValue)%',
            [
                'envName' => 'dummyEnvValue',
            ],
            'dummyEnvValue',
        ];

        yield 'string placeholder with default - not set returns default' => [
            'variabel' => '%env(string:envName:defaultEnvValue)%',
            [],
            'defaultEnvValue',
        ];

        yield 'string placeholder with underscore without default - set returns env value' => [
            'variabel' => '%env(string:SOME_ENV_VARIABLE_NAME)%',
            [
                'SOME_ENV_VARIABLE_NAME' => 'dummyEnvValue',
            ],
            'dummyEnvValue',
        ];
    }

    /**
     * @test
     * @dataProvider envPlaceholderReplacerReplacesWithExpectedValueDataProvider
     *
     * @param string $variabel
     * @param array<string, mixed> $setEnvVars
     * @param string $expectedResult
     */
    public function envPlaceholderReplacerReplacesWithExpectedValue(string $variabel, array $setEnvVars, string $expectedResult): void
    {
        $this->setEnvVars($setEnvVars);
        $replacer = new EnvPlaceholderReplacer();

        $result = $replacer->replace($variabel);
        self::assertSame($expectedResult, $result);
    }

    /**
     * @test
     */
    public function invalidEnvPlaceholderTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $replacer = new EnvPlaceholderReplacer();
        $replacer->replace('%env(invalid:envName:defaultValue');
    }

    /**
     * @param array<string, mixed> $envSetVars
     */
    protected function setEnvVars(array $envSetVars): void
    {
        if ($envSetVars === []) {
            return;
        }
        foreach ($envSetVars as $key => $value) {
            $this->originalEnvVars[$key] = getenv($key);
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }

    protected function restoreEnvVars(): void
    {
        if ($this->originalEnvVars === []) {
            return;
        }
        foreach ($this->originalEnvVars as $key => $value) {
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
        $this->originalEnvVars = [];
    }
}
