<?php

declare(strict_types=1);

/*
 * This file is part of the "sbuerk/composer-file-provider" composer plugin.
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
        $this->previousPath = getcwd();
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

    public function correctSourceFileMatchedDataProvider(): array
    {
        $source = 'sub-path/some-file.txt';
        return [
            'default pattern #0 matches' => [
                'files' => [
                    'test-templates/fake.host.test/fake-user/parent-folder/project-folder/' . $source => 'dummy-text',
                ],
                'isDDEV' => false,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/fake-user/parent-folder/project-folder/' . $source,
                            'target' => $this->targetPath . '/' . $source,
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
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/fake-user/project-folder/' . $source,
                            'target' => $this->targetPath . '/' . $source,
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
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/fake-user/' . $source,
                            'target' => $this->targetPath . '/' . $source,
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
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/parent-folder/project-folder/' . $source,
                            'target' => $this->targetPath . '/' . $source,
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
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/project-folder/' . $source,
                            'target' => $this->targetPath . '/' . $source,
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
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake.host.test/' . $source,
                            'target' => $this->targetPath . '/' . $source,
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
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake-user/parent-folder/project-folder/' . $source,
                            'target' => $this->targetPath . '/' . $source,
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
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake-user/project-folder/' . $source,
                            'target' => $this->targetPath . '/' . $source,
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
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/fake-user/' . $source,
                            'target' => $this->targetPath . '/' . $source,
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
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/parent-folder/project-folder/' . $source,
                            'target' => $this->targetPath . '/' . $source,
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
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/project-folder/' . $source,
                            'target' => $this->targetPath . '/' . $source,
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
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/ddev/' . $source,
                            'target' => $this->targetPath . '/' . $source,
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
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => 'test-templates/default/' . $source,
                            'target' => $this->targetPath . '/' . $source,
                            'matched' => true,
                        ],
                    ],
                ],
            ],
            'no matches' => [
                'files' => [],
                'isDDEV' => false,
                'expectedItems' => [
                    'some-label' => [
                        0 => [
                            'type' => TaskStack::TYPE_FILE,
                            'source' => '',
                            'target' => $this->targetPath . '/' . $source,
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
     */
    public function correctSourceFileMatched(array $files, bool $isDDEV, array $expectedItems): void
    {
        chdir($this->sourcePath);
        $this->ensureSourceFiles($files);
        $fileProviderHandler = $this->createFileProvideHandler($isDDEV);

        $taskStack = new TaskStack();
        $fileProviderHandler->match($taskStack);
        chdir($this->previousPath);

        $items = $taskStack->items();
        $items = $this->cleanItems($items);
        self::assertSame($expectedItems, $items);
    }

    protected function cleanItems(array $items): array
    {
        foreach ($items as $type => &$subItems) {
            foreach ($subItems as $idx => &$item) {
                unset($item['provider']);
            }
        }
        return $items;
    }

    protected function createFileProvideHandler(bool $isDDEV): FileProvideHandler
    {
        return new FileProvideHandler(
            'some-label',
            '/sub-path/some-file.txt',
            $this->targetPath . '/%s%',
            $this->createPathResolver('default', $isDDEV)
        );
    }

    protected function createPathResolver(string $alias, bool $isDDEV): PathResolver
    {
        return new PathResolver(
            $alias ?: 'default',
            FilesProviderService::getDefaultResolverPaths(),
            $this->createPatternReplacer($this->sourcePath, 'test-templates/', $isDDEV)
        );
    }

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
