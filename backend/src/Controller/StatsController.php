<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\StatsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class StatsController extends AbstractController
{
    public function __construct(
        private StatsRepository $statsRepository,
    ) {
    }

    #[Route('/stats', name: 'api_stats', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function dashboard(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->json($this->statsRepository->getDashboardStats($user));
    }
}
