<?php

declare(strict_types=1);

namespace FailAid\Context;

use Behat\Testwork\Cli\Controller;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WaitOnFailure implements Controller
{
    public function configure(SymfonyCommand $command): void
    {
        $command->addOption('--wait-on-failure', null, InputOption::VALUE_REQUIRED, 'Wait on failure for specified seconds');
    }

    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        if ($time = $input->getOption('wait-on-failure')) {
            FailureContext::setWaitOnFailure($time);
        }

        return null;
    }
}
