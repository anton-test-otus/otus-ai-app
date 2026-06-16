<?php

namespace App\Controller;

use App\Entity\Note;
use App\Entity\User;
use App\Repository\NoteRepository;
use App\Service\NoteGraphService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
#[IsGranted('ROLE_USER')]
class WikiLinkController extends AbstractController
{
    private const VALID_DIRECTIONS = ['both', 'outgoing', 'incoming'];

    public function __construct(
        private NoteRepository $noteRepository,
        private NoteGraphService $noteGraphService,
    ) {
    }

    #[Route('/notes/{id}/graph', name: 'note_graph', methods: ['GET'])]
    public function getGraph(Note $note, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($note->getUser() !== $user || $note->getDeletedAt() !== null) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $depth = (int) $request->query->get('depth', (string) NoteGraphService::DEFAULT_DEPTH);
        if ($depth < NoteGraphService::MIN_DEPTH || $depth > NoteGraphService::MAX_DEPTH) {
            return $this->json(
                ['error' => sprintf('depth must be between %d and %d', NoteGraphService::MIN_DEPTH, NoteGraphService::MAX_DEPTH)],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $direction = strtolower((string) $request->query->get('direction', 'both'));
        if (!\in_array($direction, self::VALID_DIRECTIONS, true)) {
            return $this->json(
                ['error' => 'direction must be one of: both, outgoing, incoming'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        return $this->json($this->noteGraphService->buildSubgraph($note, $depth, $direction, $user));
    }

    #[Route('/notes/resolve-wikilinks', name: 'resolve_wikilinks', methods: ['POST'])]
    public function resolveWikilinks(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }
        
        $ids = $data['ids'] ?? [];
        
        if (!is_array($ids) || empty($ids)) {
            return $this->json(['error' => 'Invalid request'], 400);
        }

        $user = $this->getUser();
        $normalizedIds = array_values(array_unique(array_filter(array_map(
            static fn ($id) => is_string($id) ? strtolower(trim($id)) : '',
            $ids
        ))));

        if ($normalizedIds === []) {
            return $this->json(['error' => 'Invalid request'], 400);
        }

        $notes = $this->noteRepository->findActiveByIdsForUser($normalizedIds, $user);
        $notesById = [];
        foreach ($notes as $note) {
            $notesById[strtolower((string) $note->getId())] = $note;
        }

        $resolved = [];
        foreach ($normalizedIds as $id) {
            $note = $notesById[$id] ?? null;

            if ($note === null) {
                $resolved[$id] = null;
                continue;
            }

            $resolved[$id] = [
                'id' => (string) $note->getId(),
                'title' => $note->getTitle(),
                'updatedAt' => $note->getUpdatedAt()->format('c'),
            ];
        }

        return $this->json($resolved);
    }
}
