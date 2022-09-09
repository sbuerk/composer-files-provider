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

namespace SBUERK\ComposerFilesProvider\Config;

final class ResolverConfig implements \JsonSerializable
{
    /**
     * @var non-empty-string
     */
    private $alias = 'default';

    /**
     * @var array<non-empty-string>
     */
    private $pattern = [];

    /**
     * @return non-empty-string
     */
    public function alias(): string
    {
        return $this->alias;
    }

    /**
     * @return array<non-empty-string>
     */
    public function pattern(): array
    {
        return $this->pattern;
    }

    /**
     * @param array{alias: non-empty-string, pattern: array<non-empty-string>} $config
     * @return ResolverConfig
     */
    public static function fromArray(array $config): self
    {
        $resolverConfig = new self();
        $resolverConfig->alias = $config['alias'];
        $resolverConfig->pattern = $config['pattern'];
        return $resolverConfig;
    }

    /**
     * @return array{alias: non-empty-string, pattern: array<non-empty-string>}
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'alias' => $this->alias,
            'pattern' => $this->pattern,
        ];
    }
}
