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

namespace SBUERK\ComposerFilesProvider\Tests\Unit\Service;

use Composer\Composer;
use Composer\Config;
use Composer\Downloader\DownloadManager;
use Composer\Installer\InstallationManager;
use Composer\Package\RootPackage;
use Composer\Package\RootPackageInterface;
use Composer\Util\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use SBUERK\ComposerFilesProvider\Services\FilesProviderService;
use SBUERK\ComposerFilesProvider\Tests\Unit\TestCase;

class FilesProviderServiceTest extends TestCase
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
     * @var Filesystem
     */
    protected $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem();
        $this->previousPath = (string)getcwd();
        $this->rootPath = $this->getUniqueTmpDirectory(sha1(__CLASS__));
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
     * @return array<non-empty-string, array{extraConfig: array<non-empty-string, mixed>, expectedFilesProviderExtraConfig: array<string, mixed>}>
     */
    public function getFilesProviderExtraConfigCombinedCorrectlyDataProvider(): array
    {
        return [
            'package extra config with empty resolvers' => [
                'extraConfig' => [
                    'sbuerk/composer-files-provider' => [
                        'resolvers' => [],
                        'files' => [
                            [
                                'label' => 'dummy',
                                'source' => 'some-file.txt',
                                'target' => 'public/some-file.txt',
                                'resolver' => 'default',
                            ],
                        ],
                    ],
                ],
                'expectedFilesProviderExtraConfig' => [
                    'template-root' => 'file-templates',
                    'resolvers' => [
                        'default' => FilesProviderService::getDefaultResolverPaths(),
                    ],
                    'files' => [
                        [
                            'label' => 'dummy',
                            'source' => 'some-file.txt',
                            'target' => 'public/some-file.txt',
                            'resolver' => 'default',
                        ],
                    ],
                ],
            ],
            'package extra config with secondary resolver' => [
                'extraConfig' => [
                    'sbuerk/composer-files-provider' => [
                        'resolvers' => [
                            'custom' => [
                                '%t%/%u%/%s%',
                            ],
                        ],
                        'files' => [
                            [
                                'label' => 'dummy',
                                'source' => 'some-file.txt',
                                'target' => 'public/some-file.txt',
                                'resolver' => 'default',
                            ],
                        ],
                    ],
                ],
                'expectedFilesProviderExtraConfig' => [
                    'template-root' => 'file-templates',
                    'resolvers' => [
                        'default' => FilesProviderService::getDefaultResolverPaths(),
                        'custom' => [
                            '%t%/%u%/%s%',
                        ],
                    ],
                    'files' => [
                        [
                            'label' => 'dummy',
                            'source' => 'some-file.txt',
                            'target' => 'public/some-file.txt',
                            'resolver' => 'default',
                        ],
                    ],
                ],
            ],
            'package extra config with default resolver replace' => [
                'extraConfig' => [
                    'sbuerk/composer-files-provider' => [
                        'resolvers' => [
                            'default' => [
                                '%t%/%u%/%s%',
                            ],
                        ],
                        'files' => [
                            [
                                'label' => 'dummy',
                                'source' => 'some-file.txt',
                                'target' => 'public/some-file.txt',
                                'resolver' => 'default',
                            ],
                        ],
                    ],
                ],
                'expectedFilesProviderExtraConfig' => [
                    'template-root' => 'file-templates',
                    'resolvers' => [
                        'default' => [
                            '%t%/%u%/%s%',
                        ],
                    ],
                    'files' => [
                        [
                            'label' => 'dummy',
                            'source' => 'some-file.txt',
                            'target' => 'public/some-file.txt',
                            'resolver' => 'default',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getFilesProviderExtraConfigCombinedCorrectlyDataProvider
     *
     * @param array<non-empty-string, mixed> $extraConfig
     * @param array<non-empty-string, mixed> $expectedFilesProviderExtraConfig
     */
    public function getFilesProviderExtraConfigCombinedCorrectly(array $extraConfig, array $expectedFilesProviderExtraConfig): void
    {
        $composer = new Composer();
        $composer->setConfig($this->createComposerConfig([]));
        /** @var InstallationManager */
        $installationManager = $this->createMock(InstallationManager::class);
        $composer->setInstallationManager($installationManager);
        /** @var DownloadManager */
        $downloadManager = $this->getMockBuilder(DownloadManager::class)->disableOriginalConstructor()->getMock();
        $composer->setDownloadManager($downloadManager);
        /** @var RootPackage|MockObject $package */
        $package = $this->createMock(RootPackageInterface::class);
        $package->method('getExtra')->willReturn($extraConfig);
        $composer->setPackage($package);
        $subject = new class() extends FilesProviderService {
            /**
             * @param Composer $composer
             * @return array<non-empty-string, mixed>
             */
            public function getFilesProviderExtraConfig(Composer $composer): array
            {
                return parent::getFilesProviderExtraConfig($composer); // TODO: Change the autogenerated stub
            }
        };

        $filesProviderExtraConfig = $subject->getFilesProviderExtraConfig($composer);
        self::assertSame($expectedFilesProviderExtraConfig, $filesProviderExtraConfig);
    }

    /**
     * @param array<non-empty-string, mixed> $extraConfig
     * @return Config
     */
    protected function createComposerConfig(array $extraConfig = []): Config
    {
        $config = new Config();
        $config->merge([
            'config' => [
                'vendor-dir' => $this->rootPath . DIRECTORY_SEPARATOR . 'vendor',
                'bin-dir' => $this->rootPath . DIRECTORY_SEPARATOR . 'bin',
            ],
            'repositories' => ['packagist' => false],
            'extra' => $extraConfig,
        ]);

        return $config;
    }
}
