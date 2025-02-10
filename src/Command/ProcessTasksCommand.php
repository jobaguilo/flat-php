<?php

namespace App\Command;

use App\Controller\SubscriberController;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:process-tasks',
    description: 'Start processing tasks in the background'
)]
class ProcessTasksCommand extends Command
{
    public function __construct(
        private SubscriberController $subscriberController
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting task processor...');
        $this->subscriberController->listTasks();

        return Command::SUCCESS;
    }
}