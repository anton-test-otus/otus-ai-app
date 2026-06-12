<?php

namespace App\Controller;

use App\Entity\Note;
use App\Repository\NoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
#[IsGranted('ROLE_USER')]
class WikiLinkController extends AbstractController
{
    public function __construct(
        private NoteRepository $noteRepository
    ) {
    }

    #[Route('/notes/{id}/backlinks', name: 'note_backlinks', methods: ['GET'])]
    public function getBacklinks(Note $note): JsonResponse
    {
        $user = $this->getUser();
        
        if ($note->getUser() !== $user) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $backlinks = $this->noteRepository->findBacklinks($note);

        $result = array_map(function (Note $note) {
            return [
                'id' => $note->getId(),
                'title' => $note->getTitle(),
                'updatedAt' => $note->getUpdatedAt()->format('c'),
            ];
        }, $backlinks);

        return $this->json($result);
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
