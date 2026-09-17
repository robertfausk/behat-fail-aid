<?php

declare(strict_types=1);

namespace FailAid\Context;

use Behat\Testwork\Cli\Controller;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ClearScreenshots class.
 */
class ClearScreenshots implements Controller
{
    /**
     * Configures command to be executable by the controller.
     */
    public function configure(SymfonyCommand $command): void
    {
        $command->addOption('--clear-screenshots', null, InputOption::VALUE_NONE, 'Remove all screenshots before suite.');
    }

    /**
     * Executes controller.
     */
    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        if ($input->getOption('clear-screenshots')) {
            FailureContext::setAutoClean(true);
        }

        return null;
    }
}
