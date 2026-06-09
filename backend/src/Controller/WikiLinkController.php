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
        
        $titles = $data['titles'] ?? [];
        
        if (!is_array($titles) || empty($titles)) {
            return $this->json(['error' => 'Invalid request'], 400);
        }

        $user = $this->getUser();
        $resolved = [];

        foreach ($titles as $title) {
            $notes = $this->noteRepository->findByTitleCaseInsensitive($title, $user);
            
            if (empty($notes)) {
                $resolved[$title] = null;
            } elseif (count($notes) === 1) {
                $resolved[$title] = [
                    'id' => $notes[0]->getId(),
                    'title' => $notes[0]->getTitle(),
                    'updatedAt' => $notes[0]->getUpdatedAt()->format('c'),
                ];
            } else {
                // Multiple notes with same title - return all for disambiguation
                $resolved[$title] = array_map(function (Note $note) {
                    return [
                        'id' => $note->getId(),
                        'title' => $note->getTitle(),
                        'updatedAt' => $note->getUpdatedAt()->format('c'),
                    ];
                }, $notes);
            }
        }

        return $this->json($resolved);
    }
}
