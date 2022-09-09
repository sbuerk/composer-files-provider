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

namespace SBUERK\ComposerFilesProvider\Command;

use Composer\Command\BaseCommand;
use Composer\Composer;
use SBUERK\ComposerFilesProvider\Services\FilesProviderService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesProviderInfoCommand extends BaseCommand
{
    /**
     * @var FilesProviderService
     */
    protected $filesProviderService;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->filesProviderService = new FilesProviderService();
    }

    protected function configure(): void
    {
        $this->setName('files-provider:info');
        $this->setDescription('Display infos for the files-provider composer plugin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Composer $composer */
        $composer = $this->getComposer(true);
        $this->filesProviderService->info($composer, $this->getIO());
        return 0;
    }
}
