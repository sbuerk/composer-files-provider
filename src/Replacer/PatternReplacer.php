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

namespace SBUERK\ComposerFilesProvider\Replacer;

class PatternReplacer
{
    const PATTERN_LONG_DDEV = '%ddev%';

    const PATTERN_SHORT_PROJECT_FOLDER = '%p%';
    const PATTERN_SHORT_PROJECT_PARENT_FOLDER = '%pp%';
    const PATTERN_SHORT_PROJECT_USERNAME = '%u%';
    const PATTERN_SHORT_PROJECT_HOSTNAME = '%h%';
    const PATTERN_SHORT_SOURCE = '%s%';
    const PATTERN_SHORT_TEMPLATE = '%t%';

    /**
     * @var string
     */
    protected $projectRootPath = '';

    /**
     * @var bool
     */
    protected $isDDEV = false;

    /**
     * @var string
     */
    protected $hostname = '';

    /**
     * @var string
     */
    protected $username = '';

    /**
     * @var string
     */
    protected $templateFolder = '';

    public function __construct(string $projectRootPath, string $templateFolder)
    {
        $this->projectRootPath = rtrim($projectRootPath, '/');
        $this->templateFolder = rtrim($templateFolder, '/');
        $this->init();
    }

    protected function init(): void
    {
        $this->isDDEV = $this->determineIsDDEV();
        $this->hostname = $this->determineHostname();
        $this->username = $this->determineUserName();
    }

    protected function determineIsDDEV(): bool
    {
        return filter_var(getenv('IS_DDEV_PROJECT'), FILTER_VALIDATE_BOOLEAN) === true;
    }

    protected function determineUserName(): string
    {
        $username = $this->getEnvUser();
        if ($username !== '') {
            return $username;
        }
        $home = $this->getEnvHome();
        if ($home !== '') {
            return basename($home);
        }
        return '';
    }

    protected function determineHostname(): string
    {
        $hostname = $this->getEnvHostname();
        if ($hostname !== '') {
            return $hostname;
        }
        $hostname = (string)gethostname();
        if ($hostname !== '') {
            return $hostname;
        }
        return 'localhost';
    }

    protected function getEnvHome(): string
    {
        return (string)getenv('HOME');
    }

    protected function getEnvUser(): string
    {
        return (string)getenv('USER');
    }

    protected function getEnvHostname(): string
    {
        return (string)getenv('HOSTNAME');
    }

    public function replace(string $pattern, string $source): string
    {
        $map = $this->map($source);
        $pattern = str_replace(
            array_keys($map),
            array_values($map),
            $pattern
        );
        return $pattern;
    }

    /**
     * @param string $source
     * @return array<non-empty-string, string>
     */
    protected function map(string $source): array
    {
        $templateFolder = $this->getTemplateFolder();
        $projectFolder = $this->getProjectFolder();
        $projectParentFolder = $this->getProjectParentFolder();
        $userName = $this->getUserName();
        $hostName = $this->getHostName();
        $ddev = $this->isDDEV() ? 'ddev' : 'not-ddev';
        $source = ltrim($source, '/');

        return [
            // long
            self::PATTERN_LONG_DDEV => $ddev,

            // short
            self::PATTERN_SHORT_TEMPLATE => $templateFolder,
            self::PATTERN_SHORT_PROJECT_FOLDER => $projectFolder,
            self::PATTERN_SHORT_PROJECT_PARENT_FOLDER => $projectParentFolder,
            self::PATTERN_SHORT_PROJECT_USERNAME => $userName,
            self::PATTERN_SHORT_PROJECT_HOSTNAME => $hostName,
            self::PATTERN_SHORT_SOURCE => $source,
        ];
    }

    public function getProjectFolder(): string
    {
        return basename($this->projectRootPath);
    }

    public function getTemplateFolder(): string
    {
        return $this->templateFolder;
    }

    public function getProjectParentFolder(): string
    {
        return basename(dirname($this->projectRootPath));
    }

    public function getUserName(): string
    {
        return $this->username;
    }

    public function getHostName(): string
    {
        return $this->hostname;
    }

    public function isDDEV(): bool
    {
        return $this->isDDEV;
    }
}
