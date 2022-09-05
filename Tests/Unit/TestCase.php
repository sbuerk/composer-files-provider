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

namespace SBUERK\ComposerFilesProvider\Tests\Unit;

use Composer\Package\Package;
use Composer\Util\Filesystem;
use Composer\Util\Silencer;
use PHPUnit\Framework\TestCase as BaseTestCase;
use SBUERK\ComposerFilesProvider\Replacer\PatternReplacer;

abstract class TestCase extends BaseTestCase
{
    public function getUniqueTmpDirectory(string $hash = ''): string
    {
        $attempts = 5;
        $root = sys_get_temp_dir() . '/test-temp/files-provider-test' . ($hash !== '' ? '/' . $hash : '');
        if (is_dir($root)) {
            $filesystem = new Filesystem();
            $filesystem->removeDirectory($root);
        }
        //if (Silencer::call('mkdir', $root, 0777)) {
        if (@mkdir($root, 0777, true)) {
            return (string)realpath($root);
        }
        $root = realpath(__DIR__ . '/../..') . '/test-temp/files-provider-test' . ($hash !== '' ? '/' . $hash : '');
        if (is_dir($root)) {
            $filesystem = new Filesystem();
            $filesystem->removeDirectory($root);
        }
        //if (Silencer::call('mkdir', $root, 0777)) {
        if (@mkdir($root, 0777, true)) {
            return (string)realpath($root);
        }
        throw new \RuntimeException('Failed to create a unique temporary directory.');
    }

    /**
     * @param string $name
     * @param string $type
     * @param string $version
     * @return Package
     */
    public function createPackage($name, $type = 'library', $version = 'dev-develop')
    {
        $package = new Package($name, $version, $version);
        $package->setType($type);

        return $package;
    }

    protected function createPatternReplacer(
        string $projectRootPath,
        string $templateFolder,
        bool $isDDEV = false,
        string $hostName = 'fake.host.test',
        string $username = 'fake-user'
    ): PatternReplacer {
        return new class($projectRootPath, $templateFolder, $isDDEV, $hostName, $username) extends PatternReplacer {
            public function __construct(string $projectRootPath, string $templateFolder, bool $isDDEV, string $hostName, string $username)
            {
                parent::__construct($projectRootPath, $templateFolder);
                $this->isDDEV = $isDDEV;
                $this->hostname = $hostName;
                $this->username = $username;
            }

            protected function init(): void
            {
                // noop
            }
        };
    }
}
