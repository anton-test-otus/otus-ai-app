<?php

namespace App\Service;

use App\Entity\Note;
use App\Entity\NoteVersion;
use App\Repository\NoteVersionRepository;
use Doctrine\ORM\EntityManagerInterface;

class NoteVersionService
{
    private const MAX_VERSIONS = 50;
    private const DEBOUNCE_SECONDS = 30;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private NoteVersionRepository $versionRepository
    ) {
    }

    /**
     * Создание новой версии заметки с учетом debounce
     */
    public function createVersion(Note $note): ?NoteVersion
    {
        if ($this->shouldSkipVersion($note)) {
            return null;
        }

        $version = new NoteVersion();
        $version->setNote($note);
        $version->setTitle($note->getTitle());
        $version->setContent($note->getContent());

        $this->entityManager->persist($version);
        $this->entityManager->flush();

        $this->cleanupOldVersions($note);

        return $version;
    }

    /**
     * Проверка нужно ли пропустить создание версии (debounce)
     */
    private function shouldSkipVersion(Note $note): bool
    {
        $lastVersion = $this->versionRepository->createQueryBuilder('v')
            ->where('v.note = :note')
            ->setParameter('note', $note)
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastVersion) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $lastVersionTime = $lastVersion->getCreatedAt();
        $diff = $now->getTimestamp() - $lastVersionTime->getTimestamp();

        return $diff < self::DEBOUNCE_SECONDS;
    }

    /**
     * Удаление старых версий, если их больше MAX_VERSIONS
     */
    private function cleanupOldVersions(Note $note): void
    {
        $versions = $this->versionRepository->createQueryBuilder('v')
            ->where('v.note = :note')
            ->setParameter('note', $note)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $count = count($versions);
        
        if ($count > self::MAX_VERSIONS) {
            $versionsToDelete = array_slice($versions, self::MAX_VERSIONS);
            
            foreach ($versionsToDelete as $version) {
                $this->entityManager->remove($version);
            }
            
            $this->entityManager->flush();
        }
    }

    /**
     * Получение версий для заметки
     */
    public function getVersionsForNote(Note $note, int $limit = 50, int $offset = 0): array
    {
        return $this->versionRepository->createQueryBuilder('v')
            ->where('v.note = :note')
            ->setParameter('note', $note)
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Подсчет количества версий для заметки
     */
    public function countVersionsForNote(Note $note): int
    {
        return $this->versionRepository->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.note = :note')
            ->setParameter('note', $note)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Восстановление заметки из версии
     */
    public function restoreFromVersion(Note $note, NoteVersion $version, bool $createNewVersion = true): Note
    {
        if ($createNewVersion) {
            $this->createVersion($note);
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
}
