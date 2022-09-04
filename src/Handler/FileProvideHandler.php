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

namespace SBUERK\ComposerFilesProvider\Handler;

use Composer\Util\Filesystem;
use SBUERK\ComposerFilesProvider\Resolver\PathResolver;
use SBUERK\ComposerFilesProvider\Task\TaskStack;

class FileProvideHandler
{
    /** @var string */
    protected $label = '';

    /**
     * @var string
     */
    protected $source = '';

    /**
     * @var string
     */
    protected $target = '';

    /**
     * @var PathResolver
     */
    protected $pathResolver;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param string $source
     * @param string $target
     * @param PathResolver $pathResolversByAlias
     */
    public function __construct(string $label, string $source, string $target, PathResolver $pathResolver)
    {
        $this->label = $label;
        $this->source = $source;
        $this->target = $target;
        $this->pathResolver = $pathResolver;
        $this->filesystem = new Filesystem();
    }

    public function match(TaskStack $fileProviderTaskStack): void
    {
        $resolvedPatterns = $this->pathResolver->getResolvedPatterns($this->source);
        foreach ($resolvedPatterns as $pattern => $resolvedPattern) {
            // first hit wins
            $resolvedPattern = $this->filesystem->normalizePath($resolvedPattern);
            if (file_exists($resolvedPattern)) {
                $fileProviderTaskStack->add(
                    $this,
                    $resolvedPattern,
                    $this->filesystem->normalizePath($this->pathResolver->patternReplacer()->replace($this->source, $this->target)),
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
            $this->filesystem->normalizePath($this->pathResolver->patternReplacer()->replace($this->source, $this->target)),
            false,
            TaskStack::TYPE_FILE
        );
    }

    public function label(): string
    {
        return $this->label;
    }
}
