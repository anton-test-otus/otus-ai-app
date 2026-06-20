<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class StatsRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{
     *     notesCount: int,
     *     foldersCount: int,
     *     tagsCount: int,
     *     linksCount: int,
     *     favoritesCount: int,
     *     trashCount: int,
     *     notesByFolder: list<array{folderId: ?string, folderName: string, count: int}>,
     *     topTags: list<array{tagId: string, tagName: string, count: int}>
     * }
     */
    public function getDashboardStats(User $user): array
    {
        $userId = $user->getId()?->toRfc4122();
        if ($userId === null) {
            return $this->emptyStats();
        }

        $conn = $this->entityManager->getConnection();

        $notesCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM notes WHERE user_id = :userId AND deleted_at IS NULL',
            ['userId' => $userId],
        );

        $foldersCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM folders WHERE user_id = :userId AND deleted_at IS NULL',
            ['userId' => $userId],
        );

        $tagsCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM tags WHERE user_id = :userId',
            ['userId' => $userId],
        );

        $favoritesCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM notes WHERE user_id = :userId AND deleted_at IS NULL AND is_favorite = true',
            ['userId' => $userId],
        );

        $trashCount = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM notes WHERE user_id = :userId AND deleted_at IS NOT NULL',
            ['userId' => $userId],
        );

        $linksCount = (int) $conn->fetchOne(
            <<<SQL
            SELECT COUNT(*)
            FROM note_links nl
            INNER JOIN notes n ON n.id = nl.source_note_id
            WHERE n.user_id = :userId AND n.deleted_at IS NULL
            SQL,
            ['userId' => $userId],
        );

        $notesByFolderRows = $conn->fetchAllAssociative(
            <<<SQL
            SELECT
                n.folder_id::text AS folder_id,
                COALESCE(f.name, 'Без папки') AS folder_name,
                COUNT(*) AS note_count
            FROM notes n
            LEFT JOIN folders f ON f.id = n.folder_id
            WHERE n.user_id = :userId AND n.deleted_at IS NULL
            GROUP BY n.folder_id, f.name
            ORDER BY note_count DESC, folder_name ASC
            SQL,
            ['userId' => $userId],
        );

        $notesByFolder = array_map(
            static fn (array $row): array => [
                'folderId' => $row['folder_id'],
                'folderName' => (string) $row['folder_name'],
                'count' => (int) $row['note_count'],
            ],
            $notesByFolderRows,
        );

        $topTagRows = $conn->fetchAllAssociative(
            <<<SQL
            SELECT
                t.id::text AS tag_id,
                t.name AS tag_name,
                COUNT(DISTINCT n.id) AS note_count
            FROM tags t
            INNER JOIN note_tags nt ON nt.tag_id = t.id
            INNER JOIN notes n ON n.id = nt.note_id AND n.deleted_at IS NULL
            WHERE t.user_id = :userId
            GROUP BY t.id, t.name
            ORDER BY note_count DESC, tag_name ASC
            LIMIT 8
            SQL,
            ['userId' => $userId],
        );

        $topTags = array_map(
            static fn (array $row): array => [
                'tagId' => (string) $row['tag_id'],
                'tagName' => (string) $row['tag_name'],
                'count' => (int) $row['note_count'],
            ],
            $topTagRows,
        );

        return [
            'notesCount' => $notesCount,
            'foldersCount' => $foldersCount,
            'tagsCount' => $tagsCount,
            'linksCount' => $linksCount,
            'favoritesCount' => $favoritesCount,
            'trashCount' => $trashCount,
            'notesByFolder' => $notesByFolder,
            'topTags' => $topTags,
        ];
    }

    /**
     * @return array{
     *     notesCount: int,
     *     foldersCount: int,
     *     tagsCount: int,
     *     linksCount: int,
     *     favoritesCount: int,
     *     trashCount: int,
     *     notesByFolder: list<array{folderId: ?string, folderName: string, count: int}>,
     *     topTags: list<array{tagId: string, tagName: string, count: int}>
     * }
     */
    private function emptyStats(): array
    {
        return [
            'notesCount' => 0,
            'foldersCount' => 0,
            'tagsCount' => 0,
            'linksCount' => 0,
            'favoritesCount' => 0,
            'trashCount' => 0,
            'notesByFolder' => [],
            'topTags' => [],
        ];
    }
}
