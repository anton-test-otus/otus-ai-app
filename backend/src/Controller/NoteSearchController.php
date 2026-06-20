<?php

namespace App\Controller;

use App\Repository\NoteRepository;
use App\Security\AuthenticatedUserAssert;
use App\Service\NotePreviewService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class NoteSearchController extends AbstractController
{
    public function __construct(
        private NoteRepository $noteRepository,
        private NotePreviewService $notePreviewService,
    ) {
    }

    #[IsGranted('ROLE_USER')]
    public function search(Request $request): JsonResponse
    {
        $user = AuthenticatedUserAssert::requirePersistedUser($this->getUser());

        $query = $request->query->get('q', '');
        $folderId = $request->query->get('folderId');
        $tags = $request->query->all('tags');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo = $request->query->get('dateTo');
        $isFavorite = $request->query->get('isFavorite');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = min(100, max(1, (int) $request->query->get('perPage', 20)));

        $criteria = [
            'query' => $query,
            'folderId' => $folderId,
            'tags' => $tags,
            'isFavorite' => $isFavorite !== null ? filter_var($isFavorite, FILTER_VALIDATE_BOOLEAN) : null,
            'dateFrom' => $dateFrom ? new \DateTimeImmutable($dateFrom) : null,
            'dateTo' => $dateTo ? new \DateTimeImmutable($dateTo) : null,
        ];

        $result = $this->noteRepository->search($user, $criteria, $page, $perPage);

        $titlesById = $this->notePreviewService->prefetchWikiTitlesForNotes($result['notes'], $user);

        return $this->json([
            'data' => $result['notes'],
            'meta' => [
                'currentPage' => $page,
                'perPage' => $perPage,
                'total' => $result['total'],
                'totalPages' => (int) ceil($result['total'] / $perPage),
            ],
        ], 200, [], [
            'groups' => ['note:list'],
            NotePreviewService::CONTEXT_WIKI_TITLES_BY_ID => $titlesById,
        ]);
    }
}
