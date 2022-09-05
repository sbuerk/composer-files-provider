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
            $io->write('> ComposerFilesProvider - nothing to do.', true);
            return;
        }
        $this->processTaskStack($composer, $io, $taskStack);
    }

    protected function processTaskStack(Composer $composer, IOInterface $io, TaskStack $taskStack): void
    {
        foreach ($taskStack->items() as $alias => $items) {
            foreach ($items as $item) {
                $fileProvideHandler = $item['provider'];
                $resolvedSource = $item['source'];
                $resolvedTarget = $item['target'];
                $matched = $item['matched'];
                if (!$matched) {
                    $io->write(sprintf('> ComposerFilesProvider "%s" nothing to do', $fileProvideHandler->label()));
                    continue;
                }
                if (!file_exists($resolvedSource)) {
                    $io->writeError(sprintf('> ComposerFilesProvider "%s" error: source does not exists: %s', $fileProvideHandler->label(), $resolvedSource));
                    continue;
                }
                $this->ensureTargetFolder($resolvedTarget);
                if (!$this->filesystem->copy($resolvedSource, $resolvedTarget)) {
                    $io->writeError(sprintf('> ComposerFilesProvider "%s" error: could not copy "%s" to "%s"', $fileProvideHandler->label(), $resolvedSource, $resolvedTarget));
                    continue;
                }

                $io->write(sprintf('> ComposerFilesProvider "%s" copied "%s" to "%s"', $fileProvideHandler->label(), $resolvedSource, $resolvedTarget));
            }
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
            $sourcePattern = ltrim((string)($fileConfig['source'] ?? ''), '/');
            $targetPattern = (string)($fileConfig['target'] ?? '');
            $resolver = $this->getPathResolversForAlias((($fileConfig['resolver'] ?? '') ?: 'default'), $pathResolvers);
            if (empty($sourcePattern)) {
                $io->writeError('> ComposerFilesProvider: No source pattern set for file config: ' . \json_encode($fileConfig), true);
                continue;
            }
            if (empty($targetPattern)) {
                $io->writeError('> ComposerFilesProvider: No target pattern set for file config: ' . \json_encode($fileConfig), true);
                continue;
            }
            if (!($resolver instanceof PathResolver)) {
                $io->writeError('> ComposerFilesProvider: Could not find resolver for file config: ' . \json_encode($fileConfig), true);
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

        return $fileHandlers;
    }

    /**
     * @param string $alias
     * @param array<non-empty-string,PathResolver>$pathResolvers
     * @return PathResolver|null
     */
    protected function getPathResolversForAlias(string $alias, array $pathResolvers): ?PathResolver
    {
        if (empty($alias) || ($pathResolvers[$alias] ?? false)) {
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
            if (!is_string($alias) || empty($alias)) {
                $io->writeError('> ComposerFilesProvider: Invalid alias provided for files-provider resolver configuration: ' . $alias, true);
                continue;
            }
            if (!is_array($resolverPathPatterns) || $resolverPathPatterns === []) {
                $io->writeError('> ComposerFilesProvider: Invalid or empty path pattern provided for files-provider resolver ' . $alias . ' configuration.', true);
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

    /**
     * @param Composer $composer
     * @param IOInterface $io
     * @return array<int, array{label?: string, source?: string, target?: string, resolver?: string}>
     */
    protected function getFilesConfig(Composer $composer, IOInterface $io): array
    {
        /** @var array<int, array{label?: string, source?: string, target?: string, resolver?: string}> $filesConfig */
        $filesConfig = $this->getFilesProviderExtraConfig($composer)['files'] ?? [];
        return $filesConfig;
    }

    /**
     * @param Composer $composer
     * @return array<string, array<int, string>>
     */
    protected function getResolversConfig(Composer $composer): array
    {
        /** @var array<string, array<int, string>> $resolverConfig */
        $resolverConfig = $this->getFilesProviderExtraConfig($composer)['resolvers'] ?? [];
        return $resolverConfig;
    }

    protected function getTemplateRootFolder(Composer $composer): string
    {
        /** @var string $templateRoot */
        $templateRoot = $this->getFilesProviderExtraConfig($composer)['template-root'] ?? 'file-templates';
        return $templateRoot;
    }

    /**
     * @param Composer $composer
     * @return array<non-empty-string, mixed>
     */
    protected function getFilesProviderExtraConfig(Composer $composer): array
    {
        $config = [
            'template-root' => 'file-templates',
            'resolvers' => [
                'default' => [],
            ],
            'files' => [],
        ];
        /** @var array<int|string, mixed> $packageConfig */
        $packageConfig = $composer->getPackage()->getExtra()['sbuerk/composer-files-provider'] ?? [];
        $config = array_replace_recursive($config, $packageConfig);
        if (empty($config['resolvers']['default'])) {
            $config['resolvers']['default'] = self::getDefaultResolverPaths();
        }
        /** @var array<non-empty-string, mixed> $config */
        return $config;
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

    /**
     * @return string[]
     */
    public static function getDefaultResolverPaths(): array
    {
        return [
            '%t%/%h%/%u%/%pp%/%p%/%s%',
            '%t%/%h%/%u%/%p%/%s%',
            '%t%/%h%/%u%/%s%',
            '%t%/%h%/%pp%/%p%/%s%',
            '%t%/%h%/%p%/%s%',
            '%t%/%h%/%s%',
            '%t%/%u%/%pp%/%p%/%s%',
            '%t%/%u%/%p%/%s%',
            '%t%/%u%/%s%',
            '%t%/%pp%/%p%/%s%',
            '%t%/%p%/%s%',
            '%t%/%DDEV%/%s%',
            '%t%/default/%s%',
        ];
    }
}
