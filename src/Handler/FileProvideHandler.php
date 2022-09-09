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

namespace SBUERK\ComposerFilesProvider\Handler;

use Composer\Util\Filesystem;
use SBUERK\ComposerFilesProvider\Config\FileConfig;
use SBUERK\ComposerFilesProvider\Task\TaskStack;

class FileProvideHandler
{
    /**
     * @var FileConfig
     */
    protected $fileConfig;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(FileConfig $fileConfig)
    {
        $this->fileConfig = $fileConfig;
        $this->filesystem = new Filesystem();
    }

    public function match(TaskStack $fileProviderTaskStack): void
    {
        if ($this->fileConfig->resolver() === null) {
            return;
        }
        $resolvedPatterns = $this->fileConfig->resolver()->getResolvedPatterns($this->fileConfig->source());
        foreach ($resolvedPatterns as $pattern => $resolvedPattern) {
            // first hit wins
            $resolvedPattern = $this->filesystem->normalizePath($resolvedPattern);
            if (file_exists($resolvedPattern)) {
                $fileProviderTaskStack->add(
                    $this,
                    $resolvedPattern,
                    $this->filesystem->normalizePath($this->fileConfig->resolver()->patternReplacer()->replace($this->fileConfig->source(), $this->fileConfig->target())),
                    true,
                    TaskStack::TYPE_FILE
                );
                return;
            }
        }

        // add failed state
        $fileProviderTaskStack->add(
            $this,
            '',
            $this->filesystem->normalizePath($this->fileConfig->resolver()->patternReplacer()->replace($this->fileConfig->source(), $this->fileConfig->target())),
            false,
            TaskStack::TYPE_FILE
        );
    }

    public function label(): string
    {
        return $this->fileConfig->label();
    }

    public function source(): string
    {
        return $this->fileConfig->source();
    }

    public function target(): string
    {
        return $this->fileConfig->target();
    }

    public function resolverName(): string
    {
        return $this->fileConfig->resolverName();
    }
}
