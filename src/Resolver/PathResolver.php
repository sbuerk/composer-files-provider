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

use SBUERK\ComposerFilesProvider\Config\ResolverConfig;
use SBUERK\ComposerFilesProvider\Replacer\PatternReplacer;

class PathResolver
{
    /**
     * @var ResolverConfig
     */
    protected $resolverConfig;

    /**
     * @var \SBUERK\ComposerFilesProvider\Replacer\PatternReplacer
     */
    protected $patternReplacer;

    /**
     * @param ResolverConfig $resolverConfig
     * @param PatternReplacer $patternReplacer
     */
    public function __construct(ResolverConfig $resolverConfig, PatternReplacer $patternReplacer)
    {
        $this->resolverConfig = $resolverConfig;
        $this->patternReplacer = $patternReplacer;
    }

    /**
     * @param string $source
     * @return array<string, string>
     */
    public function getResolvedPatterns(string $source): array
    {
        $return = [];
        foreach ($this->resolverConfig->pattern() as $pattern) {
            $return[$pattern] = $this->patternReplacer->replace($pattern, $source);
        }
        return $return;
    }

    public function patternReplacer(): PatternReplacer
    {
        return $this->patternReplacer;
    }
}
