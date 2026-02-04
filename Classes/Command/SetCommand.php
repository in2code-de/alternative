<?php

declare(strict_types=1);

namespace In2code\Alternative\Command;

use In2code\Alternative\Domain\Service\AlternativeQueueService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    'alternative:set',
    'Set alternative texts for images'
)]
class SetCommand extends Command
{
    public function __construct(
        readonly private AlternativeQueueService $alternativeQueueService,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setDescription('Automatically set alternative texts for images');
        $this->addArgument('combindedIdentifier', InputArgument::OPTIONAL, 'Define storage and path', '1:/');
        $this->addArgument('enforce', InputArgument::OPTIONAL, 'Overrule existing field values', '0');
        $this->addArgument('continueOnError', InputArgument::OPTIONAL, 'Continue on errors (log and skip)', '0');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->alternativeQueueService->set(
            $input->getArgument('combindedIdentifier'),
            $input->getArgument('enforce') === '1',
            $input->getArgument('continueOnError') === '1',
            $output
        );
        $output->writeln($count . ' file(s) extended with metadata.');
        return parent::SUCCESS;
    }
}
