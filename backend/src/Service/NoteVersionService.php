<?php

namespace App\Service;

use App\Dto\NoteSnapshot;
use App\Entity\Note;
use App\Entity\NoteVersion;
use App\Repository\NoteVersionRepository;
use Doctrine\ORM\EntityManagerInterface;

class NoteVersionService
{
    private const MAX_VERSIONS = 50;
    private const CONSOLIDATION_WINDOW_MINUTES = 5;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private NoteVersionRepository $versionRepository
    ) {
    }

    /**
     * Фиксирует версию при обновлении заметки: сравнивает состояние до и после сохранения.
     */
    public function recordVersionOnUpdate(Note $note, NoteSnapshot $previousState, NoteSnapshot $newState): ?NoteVersion
    {
        if ($previousState->equals($newState)) {
            return null;
        }

        $lastVersion = $this->versionRepository->findLastVersionForNote($note);

        if ($lastVersion !== null && NoteSnapshot::fromVersion($lastVersion)->equals($newState)) {
            return null;
        }

        if ($lastVersion !== null && $this->isWithinConsolidationWindow($lastVersion)) {
            $lastVersion->setTitle($previousState->title);
            $lastVersion->setContent($previousState->content);
            $this->entityManager->flush();

            return $lastVersion;
        }

        return $this->createVersion($note, $previousState);
    }

    /**
     * Сохраняет текущее состояние заметки перед восстановлением из версии.
     */
    public function backupCurrentState(Note $note): ?NoteVersion
    {
        $currentState = NoteSnapshot::fromNote($note);
        $lastVersion = $this->versionRepository->findLastVersionForNote($note);

        if ($lastVersion !== null && NoteSnapshot::fromVersion($lastVersion)->equals($currentState)) {
            return null;
        }

        if ($lastVersion !== null && $this->isWithinConsolidationWindow($lastVersion)) {
            $lastVersion->setTitle($currentState->title);
            $lastVersion->setContent($currentState->content);
            $this->entityManager->flush();

            return $lastVersion;
        }

        return $this->createVersion($note, $currentState);
    }

    /**
     * Получение версий для заметки
     */
    public function getVersionsForNote(Note $note, int $limit = 50, int $offset = 0): array
    {
        return $this->versionRepository->findByNote($note, $limit, $offset);
    }

    /**
     * Подсчет количества версий для заметки
     */
    public function countVersionsForNote(Note $note): int
    {
        return $this->versionRepository->countByNote($note);
    }

    /**
     * Восстановление заметки из версии
     */
    public function restoreFromVersion(Note $note, NoteVersion $version, bool $createNewVersion = true): Note
    {
        if ($createNewVersion) {
            $this->backupCurrentState($note);
        }

        $note->setTitle($version->getTitle());
        $note->setContent($version->getContent());

        $this->entityManager->flush();

        return $note;
    }

    /**
     * Создание копии заметки из версии
     */
    public function createNoteFromVersion(NoteVersion $version, Note $originalNote): Note
    {
        $newNote = new Note();
        $newNote->setUser($originalNote->getUser());
        $newNote->setFolder($originalNote->getFolder());
        $newNote->setTitle($version->getTitle() . ' (Copy)');
        $newNote->setContent($version->getContent());

        $this->entityManager->persist($newNote);
        $this->entityManager->flush();

        return $newNote;
    }

    private function createVersion(Note $note, NoteSnapshot $snapshot): NoteVersion
    {
        $version = new NoteVersion();
        $version->setNote($note);
        $version->setTitle($snapshot->title);
        $version->setContent($snapshot->content);

        $this->entityManager->persist($version);
        $this->entityManager->flush();

        $this->cleanupOldVersions($note);

        return $version;
    }

    private function isWithinConsolidationWindow(NoteVersion $version): bool
    {
        $now = new \DateTimeImmutable();
        $diffSeconds = $now->getTimestamp() - $version->getCreatedAt()->getTimestamp();

        return $diffSeconds < self::CONSOLIDATION_WINDOW_MINUTES * 60;
    }

    /**
     * Удаление старых версий, если их больше MAX_VERSIONS
     */
    private function cleanupOldVersions(Note $note): void
    {
        $excessVersions = $this->versionRepository->findExcessVersions($note, self::MAX_VERSIONS);

        if ($excessVersions === []) {
            return;
        }

        foreach ($excessVersions as $version) {
            $this->entityManager->remove($version);
        }

        $this->entityManager->flush();
    }
}
