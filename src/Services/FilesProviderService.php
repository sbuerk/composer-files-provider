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

namespace SBUERK\ComposerFilesProvider\Services;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use SBUERK\ComposerFilesProvider\Handler\FileProvideHandler;
use SBUERK\ComposerFilesProvider\Replacer\PatternReplacer;
use SBUERK\ComposerFilesProvider\Resolver\PathResolver;
use SBUERK\ComposerFilesProvider\Task\TaskStack;

class FilesProviderService
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function process(Composer $composer, IOInterface $io): void
    {
        $taskStack = $this->buildTaskStack($composer, $io);
        if ($taskStack->count() === 0) {
            $io->write('file-provider - nothing to process.', true);
            return;
        }
        $this->processTaskStack($composer, $io, $taskStack);
    }

    protected function processTaskStack(Composer $composer, IOInterface $io, TaskStack $taskStack): void
    {
        foreach ($taskStack->items() as $item) {
            $fileProvideHandler = $item['provider'];
            $resolvedSource = $item['source'];
            $resolvedTarget = $item['target'];
            $matched = $item['matched'];
            if (!$matched) {
                $io->write($fileProvideHandler->label() . ' - no match');
                continue;
            }
            if (!file_exists($resolvedSource)) {
                $io->writeError($fileProvideHandler->label() . ' - source does not exist: ' . $resolvedSource);
                continue;
            }
            $this->ensureTargetFolder($resolvedTarget);
            if (!$this->filesystem->copy($resolvedSource, $resolvedTarget)) {
                $io->writeError($fileProvideHandler->label() . ' - could not provide "' . $resolvedSource . '" as "' . $resolvedTarget . '"');
                continue;
            }
            $io->write($fileProvideHandler->label() . ' - provided "' . $resolvedSource . '" as "' . $resolvedTarget . '"');
        }
    }

    protected function buildTaskStack(Composer $composer, IOInterface $io): TaskStack
    {
        $taskStack = new TaskStack();
        foreach ($this->getFileHandlers($composer, $io) as $fileHandler) {
            $fileHandler->match($taskStack);
        }
        return $taskStack;
    }

    protected function ensureTargetFolder(string $resolvedTarget): void
    {
        $this->filesystem->ensureDirectoryExists(
            $this->extractPathFromFilePath($resolvedTarget)
        );
    }

    protected function extractPathFromFilePath(string $filePath): string
    {
        return pathinfo($filePath, PATHINFO_DIRNAME);
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @return FileProvideHandler[]
     */
    protected function getFileHandlers(Composer $composer, IOInterface $io): array
    {
        $filesConfig = $this->getFilesConfig($composer, $io);
        if (!is_array($filesConfig) || $filesConfig === []) {
            return [];
        }
        $pathResolvers = $this->getPathResolvers($composer, $io);

        $fileHandlers = [];
        foreach ($filesConfig as $fileConfig) {
            $label = (string)($fileConfig['label'] ?? '');
            $sourcePattern = (string)($fileConfig['source'] ?? '');
            $targetPattern = (string)($fileConfig['target'] ?? '');
            $resolver = $this->getPathResolversForAlias((($fileConfig['resolver'] ?? '') ?: 'default'), $pathResolvers);
            if (empty($sourcePattern)) {
                $io->writeError('No source pattern set for file config: ' . \json_encode($fileConfig), true);
                continue;
            }
            if (empty($targetPattern)) {
                $io->writeError('No target pattern set for file config: ' . \json_encode($fileConfig), true);
                continue;
            }
            if (!($resolver instanceof PathResolver)) {
                $io->writeError('Could not find resolver for file config: ' . \json_encode($fileConfig), true);
                continue;
            }
            if (empty($label)) {
                $label = '"' . $sourcePattern . '"';
            }
            $fileHandlers[] = new FileProvideHandler(
                $label,
                $sourcePattern,
                $targetPattern,
                $resolver
            );
        }

        return [];
    }

    /**
     * @param string $alias
     * @param array<non-empty-string,PathResolver>$pathResolvers
     * @return PathResolver|null
     */
    protected function getPathResolversForAlias(string $alias, array $pathResolvers): ?PathResolver
    {
        if (empty($alias) || $pathResolvers[$alias] ?? false) {
            $alias = 'default';
        }
        return $pathResolvers[$alias] ?? null;
    }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @return array<non-empty-string,PathResolver>
     */
    protected function getPathResolvers(Composer $composer, IOInterface $io): array
    {
        $pathResolversByAlias = [];
        $patternReplacer = $this->getPatternReplacer($composer);
        foreach ($this->getResolversConfig($composer) as $alias => $resolverPathPatterns) {
            if (!is_string($alias) || !empty($alias)) {
                $io->writeError('Invalid alias provided for files-provider resolver configuration: ' . $alias, true);
                continue;
            }
            if (!is_array($resolverPathPatterns) || $resolverPathPatterns === []) {
                $io->writeError('Invalid or empty path pattern provided for files-provider resolver ' . $alias . ' configuration.', true);
                continue;
            }
            $pathResolversByAlias[$alias] = new PathResolver(
                $alias,
                $resolverPathPatterns,
                $patternReplacer
            );
        }
        return $pathResolversByAlias;
    }

    protected function getFilesConfig(Composer $composer, IOInterface $io): array
    {
        return $this->getFilesProviderExtraConfig($composer)['files'] ?? [];
    }

    protected function getResolversConfig(Composer $composer): array
    {
        return $this->getFilesProviderExtraConfig($composer)['resolvers'] ?? [];
    }

    protected function getTemplateRootFolder(Composer $composer): string
    {
        return $this->getFilesProviderExtraConfig($composer)['template-root'] ?? 'file-templates';
    }

    protected function getFilesProviderExtraConfig(Composer $composer): array
    {
        return array_replace_recursive(
            [
                'template-root' => 'file-templates',
                'resolvers' => [
                    'default' => self::getDefaultResolverPaths(),
                ],
                'files' => [],
            ],
            $composer->getPackage()->getExtra()['sbuerk/composer-files-provider'] ?? []
        );
    }

    protected function getPatternReplacer(Composer $composer): PatternReplacer
    {
        return new PatternReplacer(
            $this->getProjectRootPath($composer),
            $this->getTemplateRootFolder($composer)
        );
    }

    protected function getProjectRootPath(Composer $composer): string
    {
        return rtrim($composer->getInstallationManager()->getInstallPath($composer->getPackage()), '/');
    }

    public static function getDefaultResolverPaths(): array
    {
        return [
            '%t%/%h%/%u%/%pp%/%p%/%s%',
            '%t%/%h%/%u%/%p%/%s%',
            '%t%/%h%/%pp%/%p%/%s%',
            '%t%/%h%/%p%/%s%',
            '%t%/%u%/%pp%/%p%/%s%',
            '%t%/%u%/%p%/%s%',
            '%t%/%pp%/%p%/%s%',
            '%t%/%p%/%s%',
            '%t%/%DDEV%/%s%',
            '%t%/default/%s%',
        ];
    }
}
