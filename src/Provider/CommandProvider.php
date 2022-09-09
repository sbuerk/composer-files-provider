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

namespace SBUERK\ComposerFilesProvider\Provider;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use SBUERK\ComposerFilesProvider\Command\FilesProviderInfoCommand;

final class CommandProvider implements CommandProviderCapability
{
    public function getCommands()
    {
        return [
            new FilesProviderInfoCommand(),
        ];
    }
}
