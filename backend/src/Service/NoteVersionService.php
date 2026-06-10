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

    public function __construct(
        private EntityManagerInterface $entityManager,
        private NoteVersionRepository $versionRepository,
        private int $consolidationWindowMinutes,
    ) {
        if ($this->consolidationWindowMinutes < 1) {
            $this->consolidationWindowMinutes = 5;
        }
    }

    /**
     * Фиксирует версию при обновлении заметки: сравнивает состояние до и после сохранения.
     * Новая версия создаётся только если с момента предыдущего updatedAt заметки прошло ≥ N минут.
     */
    public function recordVersionOnUpdate(
        Note $note,
        NoteSnapshot $previousState,
        NoteSnapshot $newState,
        \DateTimeImmutable $previousNoteUpdatedAt,
    ): ?NoteVersion {
        if ($previousState->equals($newState)) {
            return null;
        }

        $lastVersion = $this->versionRepository->findLastVersionForNote($note);

        if ($lastVersion !== null && NoteSnapshot::fromVersion($lastVersion)->equals($newState)) {
            return null;
        }

        if ($this->isWithinConsolidationWindow($previousNoteUpdatedAt)) {
            return null;
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

    /**
     * Окно консолидации: если заметка менялась менее N минут назад — новую версию не создаём.
     */
    private function isWithinConsolidationWindow(\DateTimeImmutable $previousNoteUpdatedAt): bool
    {
        $now = new \DateTimeImmutable();
        $diffSeconds = $now->getTimestamp() - $previousNoteUpdatedAt->getTimestamp();

        return $diffSeconds < $this->consolidationWindowMinutes * 60;
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
