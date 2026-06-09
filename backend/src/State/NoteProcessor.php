<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Note;
use App\Entity\NoteLink;
use App\Repository\NoteRepository;
use App\Service\WikiLinkParser;
use App\Service\NoteVersionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class NoteProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private WikiLinkParser $wikiLinkParser,
        private NoteRepository $noteRepository,
        private NoteVersionService $versionService
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Note
    {
        if (!$data instanceof Note) {
            return null;
        }

        $user = $this->security->getUser();
        
        // Для новых заметок устанавливаем пользователя
        if (!$data->getId()) {
            $data->setUser($user);
        }

        // Обработка DELETE операций
        if ($operation->getMethod() === 'DELETE') {
            // Если заметка уже в корзине (deletedAt установлен) - удалить навсегда
            if ($data->getDeletedAt()) {
                $this->em->remove($data);
                $this->em->flush();
                return null;
            }
            
            // Иначе - soft delete (переместить в корзину)
            $data->setDeletedAt(new \DateTimeImmutable());
            $this->em->flush();
            return $data;
        }

        // Создать версию перед обновлением (PUT)
        if ($operation->getMethod() === 'PUT' && $data->getId()) {
            // Создаем версию с учетом debounce
            $this->versionService->createVersion($data);
        }

        $this->em->persist($data);
        $this->em->flush();

        // Parse and update wiki-links after save (POST/PUT)
        if (in_array($operation->getMethod(), ['POST', 'PUT'])) {
            $this->updateWikiLinks($data);
        }

        return $data;
    }

    private function updateWikiLinks(Note $note): void
    {
        // Parse wiki-links from content
        $titles = $this->wikiLinkParser->parseLinks($note->getContent());
        
        // Remove all existing outgoing links
        foreach ($note->getOutgoingLinks() as $link) {
            $this->em->remove($link);
        }
        $note->getOutgoingLinks()->clear();
        
        // Create new links
        foreach ($titles as $title) {
            // Find target notes by title (case-insensitive)
            $targetNotes = $this->noteRepository->findByTitleCaseInsensitive($title, $note->getUser());
            
            // Create link only if exactly one note found (no ambiguity)
            // Multiple matches are handled by frontend disambiguation
            if (count($targetNotes) === 1) {
                $targetNote = $targetNotes[0];
                
                // Don't create self-links
                if ($targetNote->getId()->equals($note->getId())) {
                    continue;
                }
                
                $link = new NoteLink();
                $link->setSourceNote($note);
                $link->setTargetNote($targetNote);
                
                $this->em->persist($link);
                $note->addOutgoingLink($link);
            }
        }
        
        $this->em->flush();
    }
}
