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

namespace SBUERK\ComposerFilesProvider\Plugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use SBUERK\ComposerFilesProvider\Services\FilesProviderService;

class FilesProvider implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var array<string, bool>
     */
    protected $handledEvents = [];

    public function listen(Event $event): void
    {
        if (($this->handledEvents[$event->getName()] ?? false) || ($this->handledEvents['files-provider'] ?? false)) {
            return;
        }
        $this->handledEvents[$event->getName()] = true;
        $this->handledEvents['files-provider'] = true;
        // Plugin has been uninstalled
        if (!file_exists(__FILE__) || !file_exists(dirname(__DIR__) . '/Replacer/PatternReplacer.php')) {
            return;
        }

        $filesProviderService = new FilesProviderService();
        $filesProviderService->process($event->getComposer(), $event->getIO());
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $composer->getEventDispatcher()->addSubscriber($this);
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // noop
    }

    public function install(Composer $composer, IOInterface $io): void
    {
        // noop
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // noop
    }

    /**
     * @return array<non-empty-string, array<int, mixed>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::PRE_INSTALL_CMD => ['listen', 50],
            // We need autoload dump to ensure files gets provided on first plugin installation. This can be
            // changed if composer 2.1+ support only is etablished
            ScriptEvents::PRE_AUTOLOAD_DUMP => ['listen', 50],
        ];
    }
}
