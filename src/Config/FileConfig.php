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

use SBUERK\ComposerFilesProvider\Resolver\PathResolver;

final class FileConfig implements \JsonSerializable
{
    /**
     * @var array<non-empty-string,PathResolver>
     */
    private $pathResolvers = [];

    /**
     * @var string
     */
    private $label = '';

    /**
     * @var string
     */
    private $source = '';

    /**
     * @var string
     */
    private $target = '';

    /**
     * @var string
     */
    private $resolver = 'default';

    /**
     * @param array<int|string, mixed> $config
     * @param array<non-empty-string,PathResolver> $pathResolvers
     *
     * @return FileConfig
     */
    public static function fromArray(array $config, array $pathResolvers): self
    {
        $fileConfig = new self();
        $fileConfig->pathResolvers = $pathResolvers;
        if (isset($config['label']) && is_string($config['label']) && $config['label'] !== '') {
            $fileConfig->label = $config['label'];
        }
        if (isset($config['source']) && is_string($config['source']) && $config['source'] !== '') {
            $fileConfig->source = ltrim($config['source'], '/');
        }
        if (isset($config['target']) && is_string($config['target']) && $config['target'] !== '') {
            $fileConfig->target = $config['target'];
        }
        if (isset($config['resolver']) && is_string($config['resolver']) && $config['resolver'] !== '') {
            $fileConfig->resolver = $config['resolver'];
        }
        return $fileConfig;
    }

    public function label(): string
    {
        return $this->label !== '' ? $this->label : $this->source;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function target(): string
    {
        return $this->target;
    }

    public function resolver(): ?PathResolver
    {
        return $this->getPathResolversForAlias($this->resolver, $this->pathResolvers);
    }

    public function resolverName(): string
    {
        return $this->resolver;
    }

    /**
     * @param string $alias
     * @param array<non-empty-string,PathResolver>$pathResolvers
     * @return PathResolver|null
     */
    protected function getPathResolversForAlias(string $alias, array $pathResolvers): ?PathResolver
    {
        if ($alias === '' || !(($pathResolvers[$alias] ?? null) instanceof PathResolver)) {
            $alias = 'default';
        }
        return $pathResolvers[$alias] ?? null;
    }

    /**
     * @return array<non-empty-string, string>
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'label' => $this->label(),
            'source' => $this->source(),
            'target' => $this->target(),
            'resolver' => $this->resolver,
        ];
    }
}
