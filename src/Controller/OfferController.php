<?php

namespace App\Controller;

use App\Repository\OfferRepository;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class OfferController extends AbstractController
{

    public function __construct(private OfferRepository $offerRepository) {}

    #[Route('/offer', name: 'app_offer')]
    public function index(): JsonResponse
    {
        $offers = $this->offerRepository->findAll();

        return $this->json($offers);
    }
}
