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

namespace SBUERK\ComposerFilesProvider\Tests\Unit\Replacer;

use SBUERK\ComposerFilesProvider\Tests\Unit\TestCase;

class PatternReplacerTest extends TestCase
{
    /**
     * @test
     */
    public function canBeInstantiated(): void
    {
        $subject = $this->createPatternReplacer('/fictive-path/project-parent/project-path/', 'test-templates/');
        self::assertInstanceOf(\SBUERK\ComposerFilesProvider\Replacer\PatternReplacer::class, $subject);
    }

    /**
     * @test
     */
    public function returnsFakeUser(): void
    {
        $subject = $this->createPatternReplacer('/fictive-path/project-parent/project-path/', 'test-templates/');
        self::assertSame('fake-user', $subject->getUserName());
    }

    /**
     * @test
     */
    public function returnsFakeHost(): void
    {
        $subject = $this->createPatternReplacer('/fictive-path/project-parent/project-path/', 'test-templates/');
        self::assertSame('fake.host.test', $subject->getHostName());
    }

    /**
     * @test
     */
    public function returnsTrueForIsDDEV(): void
    {
        $subject = $this->createPatternReplacer('/fictive-path/project-parent/project-path/', 'test-templates/', true);
        self::assertTrue($subject->isDDEV());
    }

    /**
     * @test
     */
    public function returnsFalseForIsDDEV(): void
    {
        $subject = $this->createPatternReplacer('/fictive-path/project-parent/project-path/', 'test-templates/');
        self::assertFalse($subject->isDDEV());

        $subject = $this->createPatternReplacer('/fictive-path/project-parent/project-path/', 'test-templates/', false);
        self::assertFalse($subject->isDDEV());
    }

    public function patternAreCorrectlyReplacedDataProvier(): array
    {
        $projectFolder = 'project-path';
        $projectParentFolder = 'project-parent';
        $defaultReplacer = $this->createPatternReplacer(
            "/fictive-path/$projectParentFolder/$projectFolder/",
            'test-templates/'
        );
        return [
            // short placeholders
            '"%u%" gets replaced' => [
                'replacer' => $defaultReplacer,
                'pattern' => '/some/paths/%u%/after/placeholder',
                'source' => 'files-to-find.ext',
                'expected' => '/some/paths/fake-user/after/placeholder',
            ],
            '"%s%" gets replaced' => [
                'replacer' => $defaultReplacer,
                'pattern' => '/some/paths/%s%',
                'source' => 'files-to-find.ext',
                'expected' => '/some/paths/files-to-find.ext',
            ],
            '"%p%" gets replaced' => [
                'replacer' => $defaultReplacer,
                'pattern' => '/some/paths/%p%/some-file.txt',
                'source' => 'files-to-find.ext',
                'expected' => "/some/paths/$projectFolder/some-file.txt",
            ],
            '"%pp%" gets replaced' => [
                'replacer' => $defaultReplacer,
                'pattern' => '/some/paths/%pp%/some-file.txt',
                'source' => 'files-to-find.ext',
                'expected' => "/some/paths/$projectParentFolder/some-file.txt",
            ],
            'project and project parentfolder concated with dash are replaced (short)' => [
                'replacer' => $defaultReplacer,
                'pattern' => '/some/paths/%pp%-%p%/some-file.txt',
                'source' => 'files-to-find.ext',
                'expected' => "/some/paths/$projectParentFolder-$projectFolder/some-file.txt",
            ],
            '"%h%" gets replaced' => [
                'replacer' => $defaultReplacer,
                'pattern' => '/some/paths/%h%/some-file.txt',
                'source' => 'files-to-find.ext',
                'expected' => '/some/paths/fake-host/some-file.ext',
            ],
            '"%t" gets replaced' => [
                'replacer' => $defaultReplacer,
                'pattern' => '%t%/some/paths/some-file.txt',
                'source' => 'files-to-find.ext',
                'expected' => 'test-templates/some/paths/some-file.ext',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider patternAreCorrectlyReplacedDataProvier
     */
    protected function patternAreCorrectlyReplaced(\SBUERK\ComposerFilesProvider\Replacer\PatternReplacer $replacer, string $pattern, string $source, string $expected): void
    {
        self::assertSame($expected, $replacer->replace($pattern, $source));
    }
}
