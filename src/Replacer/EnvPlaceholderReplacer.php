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

namespace SBUERK\ComposerFilesProvider\Replacer;

class EnvPlaceholderReplacer
{
    /**
     * @var non-empty-string[]
     */
    protected $validTypes = [
        'string',
    ];

    public function replace(string $variable): string
    {
        [
            $type,
            $envVariableName,
            $defaultValue
        ] = $this->splitVariableParts($variable);
        if (!$this->isValidType($type)) {
            throw new \InvalidArgumentException(sprintf('Env placeholder "%s" contains invalid type "%s".', $variable, $type));
        }
        return $this->callTypeMethod($type, $envVariableName, $defaultValue);
    }

    /**
     * @param string $variable
     * @return array{0: string, 1: string, 2: string}
     */
    protected function splitVariableParts(string $variable): array
    {
        $parts = array_pad(explode(':', trim(str_replace(['%env(', ')%'], '', $variable)), 3), 3, '');
        return [
            (string)($parts[0] ?? ''),
            (string)($parts[1] ?? ''),
            (string)($parts[2] ?? ''),
        ];
    }

    protected function isValidType(string $type): bool
    {
        return in_array($type, $this->validTypes, true);
    }

    protected function callTypeMethod(string $type, string $envVariableName, string $defaultValue): string
    {
        if ($this->isValidType($type)) {
            switch ($type) {
                case 'string':
                    return $this->replaceStringEnvVariable($envVariableName, $defaultValue);
                default:
            }
        }
        return $defaultValue;
    }

    protected function replaceStringEnvVariable(string $envVariableName, string $defaultValue): string
    {
        $value = getenv($envVariableName);
        if (!is_string($value)) {
            return $defaultValue;
        }
        return $value !== '' ? $value : $defaultValue;
    }
}
