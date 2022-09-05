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

namespace SBUERK\ComposerFilesProvider\Resolver;

use SBUERK\ComposerFilesProvider\Replacer\PatternReplacer;

class PathResolver
{
    /**
     * @var string
     */
    protected $alias = '';

    /**
     * @var \SBUERK\ComposerFilesProvider\Replacer\PatternReplacer
     */
    protected $patternReplacer;

    /**
     * @var array<int, string>
     */
    protected $patterns = [];

    /**
     * @param string $alias
     * @param array<int, string> $patterns
     * @param PatternReplacer $patternReplacer
     */
    public function __construct(string $alias, array $patterns, PatternReplacer $patternReplacer)
    {
        $this->patternReplacer = $patternReplacer;
        $this->alias = $alias;
        foreach ($patterns as $pattern) {
            if ($pattern === '') {
                continue;
            }
            $this->patterns[] = $pattern;
        }
    }

    /**
     * @param string $source
     * @return array<string, string>
     */
    public function getResolvedPatterns(string $source): array
    {
        $return = [];
        foreach ($this->patterns as $pattern) {
            $return[$pattern] = $this->patternReplacer->replace($pattern, $source);
        }
        return $return;
    }

    public function patternReplacer(): PatternReplacer
    {
        return $this->patternReplacer;
    }
}
