<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Service\ProjetStatsCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class ApiStatsController extends AbstractController
{
    public function __construct(
        private ProjetStatsCalculator $statsCalculator
    ) {}

    #[Route('/projets/{id}/stats', name: 'projet_stats', methods: ['GET'])]
    public function getProjetStats(Projet $projet): JsonResponse
    {
        $stats = $this->statsCalculator->getProjetStats($projet);

        return new JsonResponse([
            'projet' => [
                'id' => $projet->getId(),
                'nom' => $projet->getNom(),
            ],
            'stats' => $stats,
        ]);
    }

    #[Route('/projets/stats/all', name: 'all_projets_stats', methods: ['GET'])]
    public function getAllProjetsStats(): JsonResponse
    {
        $stats = $this->statsCalculator->getAllProjetsStats();

        return new JsonResponse([
            'stats' => $stats,
        ]);
    }

    #[Route('/projets/{id}/stats/quick', name: 'projet_quick_stats', methods: ['GET'])]
    public function getQuickStats(Projet $projet): JsonResponse
    {
        $stats = $this->statsCalculator->getQuickStats($projet);

        return new JsonResponse([
            'projet' => [
                'id' => $projet->getId(),
                'nom' => $projet->getNom(),
            ],
            'stats' => $stats,
        ]);
    }
}
