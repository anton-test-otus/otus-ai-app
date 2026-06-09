<?php

namespace App\Controller;

use App\Repository\NoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notes')]
#[IsGranted('ROLE_USER')]
class NoteSearchController extends AbstractController
{
    public function __construct(
        private NoteRepository $noteRepository
    ) {
    }

    #[Route('/search', name: 'api_notes_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $query = $request->query->get('q', '');
        $folderId = $request->query->get('folderId');
        $tags = $request->query->all('tags');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = min(100, max(1, (int) $request->query->get('perPage', 20)));

        $criteria = [
            'query' => $query,
            'folderId' => $folderId,
            'tags' => $tags,
            'dateFrom' => $dateFrom ? new \DateTimeImmutable($dateFrom) : null,
            'dateTo' => $dateTo ? new \DateTimeImmutable($dateTo) : null,
        ];

        $result = $this->noteRepository->search($user, $criteria, $page, $perPage);

        return $this->json([
            'data' => $result['notes'],
            'meta' => [
                'currentPage' => $page,
                'perPage' => $perPage,
                'total' => $result['total'],
                'totalPages' => (int) ceil($result['total'] / $perPage),
            ],
        ], 200, [], ['groups' => ['note:read']]);
    }
}
