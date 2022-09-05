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

namespace SBUERK\ComposerFilesProvider\Tests\Unit\Resolver;

use SBUERK\ComposerFilesProvider\Resolver\PathResolver;
use SBUERK\ComposerFilesProvider\Tests\Unit\TestCase;

class PathResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     * @dataProvider pathResolverStackPatternsReplacedProperlyDataProvider
     *
     * @param string $source
     * @param PathResolver $resolver
     * @param array<non-empty-string, string> $expectedPattern
     */
    public function pathResolverStackPatternsReplacedProperly(string $source, PathResolver $resolver, array $expectedPattern): void
    {
        $actual = $resolver->getResolvedPatterns($source);
        self::assertSame($expectedPattern, $actual);
    }

    /**
     * @return array<non-empty-string, array{source: string, resolver: PathResolver, expectedPattern: array<non-empty-string, string>}>
     */
    public function pathResolverStackPatternsReplacedProperlyDataProvider(): array
    {
        $source = '/anyfolder/file.ext';
        $patternsDDEV = $this->createPatternWithExpectedValues(true, $source);
        $patternsNotDDEV = $this->createPatternWithExpectedValues(false, $source);
        $DDEV = $this->createPathResolver('default', array_keys($patternsDDEV), true);
        $notDDEV = $this->createPathResolver('default', array_keys($patternsNotDDEV), false);
        return [
            'not ddev' => [
                'source' => $source,
                'resolver' => $notDDEV,
                'expectedPattern' => $patternsNotDDEV,
            ],
            'is ddev' => [
                'source' => $source,
                'resolver' => $DDEV,
                'expectedPattern' => $patternsDDEV,
            ],
        ];
    }

    /**
     * @param bool $isDDEV
     * @param string $source
     * @return array<non-empty-string, string>
     */
    protected function createPatternWithExpectedValues(bool $isDDEV, string $source): array
    {
        $template = 'test-templates';
        $hostname = 'fake.host.test';
        $ddev = $isDDEV ? 'ddev' : 'not-ddev-should-not-be-used';
        $username = 'fake-user';
        $projectFolder = 'project-path';
        $projectParentFolder = 'project-parent';
        $source = ltrim($source, '/');
        return [
            '%t%/%h%/%u%/%pp%/%p%/%s%' => implode('/', [$template, $hostname, $username, $projectParentFolder, $projectFolder, $source]),
            '%t%/%h%/%u%/%p%/%s%' => implode('/', [$template, $hostname, $username, $projectFolder, $source]),
            '%t%/%h%/%u%/%s%' => implode('/', [$template, $hostname, $username, $source]),
            '%t%/%h%/%pp%/%p%/%s%' => implode('/', [$template, $hostname, $projectParentFolder, $projectFolder, $source]),
            '%t%/%h%/%p%/%s%' => implode('/', [$template, $hostname, $projectFolder, $source]),
            '%t%/%h%/%s%' => implode('/', [$template, $hostname, $source]),
            '%t%/%u%/%pp%/%p%/%s%' => implode('/', [$template, $username, $projectParentFolder, $projectFolder, $source]),
            '%t%/%u%/%p%/%s%' => implode('/', [$template, $username, $projectFolder, $source]),
            '%t%/%u%/%s%' => implode('/', [$template, $username, $source]),
            '%t%/%pp%/%p%/%s%' => implode('/', [$template, $projectParentFolder, $projectFolder, $source]),
            '%t%/%p%/%s%' => implode('/', [$template, $projectFolder, $source]),
            '%t%/%DDEV%/%s%' => implode('/', [$template, $ddev, $source]),
            '%t%/default/%s%' => implode('/', [$template, 'default', $source]),
        ];
    }

    /**
     * @param string $alias
     * @param array<int, string> $patterns
     * @param bool $isDDEV
     * @return PathResolver
     */
    protected function createPathResolver(string $alias, array $patterns, bool $isDDEV): PathResolver
    {
        return new PathResolver(
            $alias !== '' ? $alias : 'default',
            $patterns,
            $this->createPatternReplacer('/fictive-path/project-parent/project-path/', 'test-templates/', $isDDEV)
        );
    }
}
