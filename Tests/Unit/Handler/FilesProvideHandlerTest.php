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

namespace SBUERK\ComposerFilesProvider\Tests\Unit\Handler;

use Composer\Util\Filesystem;
use SBUERK\ComposerFilesProvider\Config\FileConfig;
use SBUERK\ComposerFilesProvider\Config\ResolverConfig;
use SBUERK\ComposerFilesProvider\Handler\FileProvideHandler;
use SBUERK\ComposerFilesProvider\Resolver\PathResolver;
use SBUERK\ComposerFilesProvider\Services\FilesProviderService;
use SBUERK\ComposerFilesProvider\Task\TaskStack;
use SBUERK\ComposerFilesProvider\Tests\Unit\TestCase;

class FilesProvideHandlerTest extends TestCase
{
    /**
     * @var string
     */
    protected $previousPath = '';

    /**
     * @var string
     */
    protected $rootPath = '';

    /**
     * @var string
     */
    protected $sourcePath = '';

    /**
     * @var string
     */
    protected $targetPath = '';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->previousPath = (string)getcwd();
        $this->rootPath = $this->getUniqueTmpDirectory(sha1(__CLASS__));
        $this->sourcePath = $this->rootPath . '/parent-folder/project-folder';
        $this->targetPath = $this->rootPath . '/target';
        $this->filesystem->ensureDirectoryExists($this->sourcePath);
        $this->filesystem->ensureDirectoryExists($this->targetPath);
    }

    protected function tearDown(): void
    {
        chdir($this->previousPath);
        if (is_dir($this->rootPath)) {
            $this->filesystem->removeDirectory($this->rootPath);
        }
        parent::tearDown();
    }

    /**
     * @return array<string, array{files: array<non-empty-string, string>, isDDEV: bool, expectedItems: array<non-empty-string, list<array{type: string, source: string, target: string, matched: bool}>>}>
     */
    public function correctSourceFileMatchedDataProvider(): array
    {
        $source = 'sub-path/some-file.txt';
        $sourceWithEmptyPathSegment = 'sub-path//some-file.txt';
        $fileHandlerSource = '/sub-path/some-file.txt';
        $targetSourcePattern = '%s%';
        $targetWithoutPattern = $source;
        return [
            // target pattern
            'default pattern #0 matches' => [
                'files' => [
                    'test-templates/fake.host.test/fake-user/parent-folder/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/fake-user/parent-folder/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #1 matches' => [
                'files' => [
                    'test-templates/fake.host.test/fake-user/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/fake-user/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #2 matches' => [
                'files' => [
                    'test-templates/fake.host.test/fake-user/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/fake-user/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #3 matches' => [
                'files' => [
                    'test-templates/fake.host.test/parent-folder/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/parent-folder/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #4 matches' => [
                'files' => [
                    'test-templates/fake.host.test/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #5 matches' => [
                'files' => [
                    'test-templates/fake.host.test/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #6 matches' => [
                'files' => [
                    'test-templates/fake-user/parent-folder/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake-user/parent-folder/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #7 matches' => [
                'files' => [
                    'test-templates/fake-user/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake-user/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #8 matches' => [
                'files' => [
                    'test-templates/fake-user/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake-user/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #9 matches' => [
                'files' => [
                    'test-templates/parent-folder/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/parent-folder/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #10 matches' => [
                'files' => [
                    'test-templates/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #11 matches' => [
                'files' => [
                    'test-templates/ddev/' . $source => 'dummy-text',
                ],
                'isDDEV' => true,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/ddev/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #12 matches' => [
                'files' => [
                    'test-templates/default/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/default/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'no matches' => [
                'files' => [],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetSourcePattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => '',
                            'target' => ltrim($source, '/'),
                            'matched' => false,
                        ],
                    ],
                ],
            ],
            // target without pattern
            'default pattern #0 matches without target pattern' => [
                'files' => [
                    'test-templates/fake.host.test/fake-user/parent-folder/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/fake-user/parent-folder/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #1 matches without target pattern' => [
                'files' => [
                    'test-templates/fake.host.test/fake-user/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/fake-user/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #2 matches without target pattern' => [
                'files' => [
                    'test-templates/fake.host.test/fake-user/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/fake-user/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #3 matches without target pattern' => [
                'files' => [
                    'test-templates/fake.host.test/parent-folder/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/parent-folder/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #4 matches without target pattern' => [
                'files' => [
                    'test-templates/fake.host.test/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #5 matches without target pattern' => [
                'files' => [
                    'test-templates/fake.host.test/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #6 matches without target pattern' => [
                'files' => [
                    'test-templates/fake-user/parent-folder/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake-user/parent-folder/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #7 matches without target pattern' => [
                'files' => [
                    'test-templates/fake-user/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake-user/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #8 matches without target pattern' => [
                'files' => [
                    'test-templates/fake-user/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake-user/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #9 matches without target pattern' => [
                'files' => [
                    'test-templates/parent-folder/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/parent-folder/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #10 matches without target pattern' => [
                'files' => [
                    'test-templates/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/project-folder/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #11 matches without target pattern' => [
                'files' => [
                    'test-templates/ddev/' . $source => 'dummy-text',
                ],
                'isDDEV' => true,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/ddev/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'default pattern #12 matches without target pattern' => [
                'files' => [
                    'test-templates/default/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/default/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'empty path segment gets normalized' => [
                'files' => [
                    'test-templates/default/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'source' => $sourceWithEmptyPathSegment,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/default/' . $source,
                            'target' => ltrim($source, '/'),
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'no matches without target pattern' => [
                'files' => [],
                'isDDEV' => false,
                'source' => $source,
                'target' => $targetWithoutPattern,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => '',
                            'target' => ltrim($source, '/'),
                            'matched' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider correctSourceFileMatchedDataProvider
     * @param array<non-empty-string, string> $files
     * @param bool $isDDEV
     * @param non-empty-string $source
     * @param non-empty-string $target
     * @param array<non-empty-string, list<array{type: string, source: string, target: string, matched: bool}>> $expectedItems
     */
    public function correctSourceFileMatched(array $files, bool $isDDEV, string $source, string $target, array $expectedItems): void
    {
        chdir($this->sourcePath);
        $this->ensureSourceFiles($files);
        $fileProviderHandler = $this->createFileProvideHandler($isDDEV, $source, $target);

        $taskStack = new TaskStack();
        $fileProviderHandler->match($taskStack);
        chdir($this->previousPath);

        $items = $taskStack->items();
        $items = $this->cleanItems($items);
        self::assertSame($expectedItems, $items);
    }

    /**
     * @param array<string, array<int, array{type: string, source: string, target: string, provider: FileProvideHandler, matched: bool}>> $items
     * @return array<string, array<int, array{type: string, source: string, target: string, matched: bool}>>
     */
    protected function cleanItems(array $items): array
    {
        $return = [];
        foreach ($items as $type => $subItems) {
            foreach ($subItems as $idx => $item) {
                foreach ($item as $key => $value) {
                    if ($key === 'provider') {
                        continue;
                    }
                    $return[$type][$idx][$key] = $value;
                }
            }
        }
        /** @var array<string, array<int, array{type: string, source: string, target: string, matched: bool}>> $return */
        return $return;
    }

    protected function createFileProvideHandler(bool $isDDEV, string $source = '/sub-path/some-file.txt', string $target = '%s%'): FileProvideHandler
    {
        return new FileProvideHandler(
            FileConfig::fromArray(
                [
                    'label' => 'some-label',
                    'source' => $source,
                    'target' => $target,
                    'resolver' => 'default',
                ],
                [
                    'default' => $this->createPathResolver('default', $isDDEV),
                ]
            )
        );
    }

    protected function createPathResolver(string $alias, bool $isDDEV): PathResolver
    {
        return new PathResolver(
            ResolverConfig::fromArray(['alias' => ($alias !== '' ? $alias : 'default'), 'pattern' => FilesProviderService::getDefaultResolverPaths()]),
            $this->createPatternReplacer($this->sourcePath, 'test-templates/', $isDDEV)
        );
    }

    /**
     * @param array<non-empty-string, string> $files
     */
    protected function ensureSourceFiles(array $files): void
    {
        $this->filesystem->ensureDirectoryExists($this->sourcePath);
        foreach ($files as $file => $content) {
            $path = $this->filesystem->normalizePath($this->sourcePath . DIRECTORY_SEPARATOR . $file);
            $this->filesystem->ensureDirectoryExists(dirname($path));
            file_put_contents($path, $content);
        }
    }
}
