<?php

declare(strict_types=1);

/*
 * This file is part of the "file-provider" composer plugin.
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
     */
    public function pathResolverStackPatternsReplacedProperly(string $source, PathResolver $resolver, array $expected): void
    {
        $actual = $resolver->getResolvedPatterns($source);
        self::assertSame($expected, $actual);
    }

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
                'expected' => $patternsNotDDEV,
            ],
            'is ddev' => [
                'source' => $source,
                'resolver' => $DDEV,
                'expected' => $patternsDDEV,
            ],
        ];
    }

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
            '%t%/%h%/%pp%/%p%/%s%' => implode('/', [$template, $hostname, $projectParentFolder, $projectFolder, $source]),
            '%t%/%h%/%p%/%s%' => implode('/', [$template, $hostname, $projectFolder, $source]),
            '%t%/%u%/%pp%/%p%/%s%' => implode('/', [$template, $username, $projectParentFolder, $projectFolder, $source]),
            '%t%/%u%/%p%/%s%' => implode('/', [$template, $username, $projectFolder, $source]),
            '%t%/%pp%/%p%/%s%' => implode('/', [$template, $projectParentFolder, $projectFolder, $source]),
            '%t%/%p%/%s%' => implode('/', [$template, $projectFolder, $source]),
            '%t%/%DDEV%/%s%' => implode('/', [$template, $ddev, $source]),
            '%t%/default/%s%' => implode('/', [$template, 'default', $source]),
        ];
    }

    protected function createPathResolver(string $alias, array $patterns, bool $isDDEV): PathResolver
    {
        return new PathResolver(
            $alias ?: 'default',
            $patterns,
            $this->createPatternReplacer('/fictive-path/project-parent/project-path/', 'test-templates/', $isDDEV)
        );
    }
}