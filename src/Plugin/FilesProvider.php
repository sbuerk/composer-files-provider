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
    protected $handledEvents = [];

    public function listen(Event $event)
    {
        if ($this->handledEvents[$event->getName()] ?? false) {
            return;
        }
        $this->handledEvents[$event->getName()] = true;

        $event->getIO()->write('> FilesProvider event: ' . $event->getName(), true);
        $filesProviderService = new FilesProviderService();
        $filesProviderService->process($event->getComposer(), $event->getIO());
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $io->write('> FilesProvider plugin activated', true);
        $composer->getEventDispatcher()->addSubscriber($this);
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        $io->write('> FilesProvider plugin deactivated', true);
    }

    public function install(Composer $composer, IOInterface $io)
    {
        $io->write('> FilesProvider plugin installed', true);
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        $io->write('> FilesProvider plugin uninstall', true);
    }

    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::PRE_INSTALL_CMD => ['listen', 50],
        ];
    }
}
