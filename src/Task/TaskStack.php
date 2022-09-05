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

namespace SBUERK\ComposerFilesProvider\Task;

use SBUERK\ComposerFilesProvider\Handler\FileProvideHandler;

class TaskStack
{
    const TYPE_FILE = 'file';

    /**
     * @var array<string, array<int, array{type: string, source: string, target: string, provider: FileProvideHandler, matched: bool}>>
     */
    protected $items = [];

    public function add(FileProvideHandler $fileProvideHandler, string $resolvedSource, string $resolvedTarget, bool $matched, string $type): void
    {
        if ($type !== self::TYPE_FILE) {
            return;
        }
        $this->items[$fileProvideHandler->label()][] = [
            'type' => $type,
            'source' => $resolvedSource,
            'target' => $resolvedTarget,
            'provider' => $fileProvideHandler,
            'matched' => $matched,
        ];
    }

    /**
     * @return array<string, array<int, array{type: string, source: string, target: string, provider: FileProvideHandler, matched: bool}>>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
