<?php

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\NoteSnapshot;
use App\Entity\Note;
use App\Entity\NoteLink;
use App\Repository\NoteRepository;
use App\Service\WikiLinkParser;
use App\Service\NoteVersionService;
use App\Service\UserSettingsResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class NoteProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private WikiLinkParser $wikiLinkParser,
        private NoteRepository $noteRepository,
        private NoteVersionService $versionService,
        private UserSettingsResolver $userSettingsResolver,
        private PersistProcessor $persistProcessor,
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

        $previousState = null;
        $newState = null;
        $previousNoteUpdatedAt = null;
        if (
            $operation->getMethod() === 'PUT'
            && isset($context['previous_data'])
            && $context['previous_data'] instanceof Note
        ) {
            $previousNote = $context['previous_data'];
            $previousState = NoteSnapshot::fromNote($previousNote);
            $newState = NoteSnapshot::fromNote($data);
            $previousNoteUpdatedAt = $previousNote->getUpdatedAt();
        }

        $note = $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        if (!$note instanceof Note) {
            return null;
        }

        if ($previousState !== null && $newState !== null && $previousNoteUpdatedAt !== null) {
            $noteOwner = $note->getUser();
            $consolidationWindowMinutes = $noteOwner !== null
                ? $this->userSettingsResolver->resolveVersionConsolidationWindowMinutes($noteOwner)
                : 5;

            $this->versionService->recordVersionOnUpdate(
                $note,
                $previousState,
                $newState,
                $previousNoteUpdatedAt,
                $consolidationWindowMinutes,
            );
        }

        // Parse and update wiki-links after save
        if (in_array($operation->getMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            $this->updateWikiLinks($note);
        }

        return $note;
    }

    private function updateWikiLinks(Note $note): void
    {
        $targetIds = array_values(array_unique($this->wikiLinkParser->parseLinks($note->getContent())));

        foreach ($note->getOutgoingLinks() as $link) {
            $this->em->remove($link);
        }
        $note->getOutgoingLinks()->clear();

        $user = $note->getUser();
        if ($targetIds === [] || $user === null) {
            $this->em->flush();
            return;
        }

        $sourceId = $note->getId() !== null ? strtolower((string) $note->getId()) : null;
        $idsToFetch = array_values(array_filter(
            $targetIds,
            static fn (string $targetId) => $sourceId === null || $targetId !== $sourceId,
        ));

        if ($idsToFetch === []) {
            $this->em->flush();
            return;
        }

        $notesById = [];
        foreach ($this->noteRepository->findActiveByIdsForUser($idsToFetch, $user) as $targetNote) {
            $notesById[strtolower((string) $targetNote->getId())] = $targetNote;
        }

        foreach ($idsToFetch as $targetId) {
            $targetNote = $notesById[$targetId] ?? null;
            if ($targetNote === null) {
                continue;
            }

            $link = new NoteLink();
            $link->setSourceNote($note);
            $link->setTargetNote($targetNote);

            $this->em->persist($link);
            $note->addOutgoingLink($link);
        }

        $this->em->flush();
    }
}
