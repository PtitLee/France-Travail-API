<?php

namespace App\Command;

use App\Entity\Offer;
use App\Repository\OfferRepository;
use App\Service\FranceTravailAPIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:offer:import-from-api',
    description: 'Add a short description for your command',
)]
class OfferImportFromApiCommand extends Command
{
    public function __construct(
        private readonly FranceTravailAPIService $franceTravailAPIService,
        private readonly EntityManagerInterface $entityManager,
        private readonly OfferRepository $offerRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Import Offers from France Travail API.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importing Offers from France Travail API');

        $OffersFromAPI = $this->franceTravailAPIService->getOffers();

        $importedOffers = 0;
        foreach ($io->progressIterate($OffersFromAPI) as $currentOffer) {
            if ($this->offerRepository->findOneBy(['id' => $currentOffer->getId()]) === null) {
                $this->entityManager->persist($currentOffer);
                $importedOffers++;
            }
        }

        $this->entityManager->flush();

        $io->text($importedOffers . ' Imported Offers');
        return Command::SUCCESS;
    }
}
