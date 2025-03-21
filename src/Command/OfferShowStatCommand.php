<?php

namespace App\Command;

use App\Repository\OfferRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:offer:show-stat',
    description: 'Add a short description for your command',
)]
class OfferShowStatCommand extends Command
{
    public function __construct(private readonly OfferRepository $offerRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Show stats of Offers.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Showing Stats of Offers');

        //TODO: Display country and compagny stats
        $io->table(
            ['Contract Type', 'Total'],
            $this->offerRepository->getContractTypeStats()
        );

        return Command::SUCCESS;
    }
}
