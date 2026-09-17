<?php

declare(strict_types=1);

namespace FailAid\Context;

use Behat\Testwork\Cli\Controller;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FeedbackOnFailure implements Controller
{
    public function configure(SymfonyCommand $command): void
    {
        $command->addOption('--feedback-on-failure', null, InputOption::VALUE_NONE, 'Display failure information after failure, used when running tets in progress format.');
    }

    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        if ($input->getOption('feedback-on-failure')) {
            FailureContext::setFeedbackOnFailure(true);
        }

        return null;
    }
}
