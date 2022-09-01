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
     * @var array
     */
    protected $patterns = [];

    public function __construct(string $alias, array $patterns, PatternReplacer $patternReplacer)
    {
        $this->patternReplacer = $patternReplacer;
        $this->alias = $alias;
        foreach ($patterns as $pattern) {
            if (!is_string($pattern)) {
                throw new \InvalidArgumentException('$pattern must be a valid string', 1662056638);
            }
            if (empty($pattern)) {
                continue;
            }
            $this->patterns[] = $pattern;
        }
    }

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