<?php

namespace App\DemoSeed;

use App\Entity\Folder;
use App\Entity\Note;
use App\Entity\NoteLink;
use App\Entity\NoteVersion;
use App\Entity\Tag;
use App\Entity\User;
use App\Service\NoteLinkSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DemoUniverseSeeder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private NoteLinkSyncService $noteLinkSyncService,
    ) {
    }

    public function seed(DemoUniverseDefinition $definition): DemoSeedResult
    {
        $now = new \DateTimeImmutable();

        $user = new User();
        $user->setEmail($definition->email);
        $user->setRoles($definition->roles);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, DemoUniverseDefinition::DEMO_PASSWORD),
        );
        $this->entityManager->persist($user);

        $foldersByPath = $this->createFolders($definition->folders, $user);
        $tagsByName = $this->createTags($definition->tags, $user);

        /** @var array<string, Note> $notesByKey */
        $notesByKey = [];
        $favoriteCount = 0;

        foreach ($definition->notes as $noteDefinition) {
            if ($noteDefinition->isFavorite && $noteDefinition->versions === []) {
                throw new \InvalidArgumentException(sprintf(
                    'Избранная заметка "%s" должна иметь хотя бы одну версию',
                    $noteDefinition->key,
                ));
            }
        }

        foreach ($definition->notes as $noteDefinition) {

            if ($noteDefinition->isFavorite) {
                ++$favoriteCount;
            }

            $note = new Note();
            $note->setUser($user);
            $note->setTitle($noteDefinition->title);
            $note->setContent($noteDefinition->content);
            $note->setIsFavorite($noteDefinition->isFavorite);

            if ($noteDefinition->folderPath !== null) {
                $folder = $foldersByPath[$noteDefinition->folderPath] ?? null;
                if ($folder === null) {
                    throw new \InvalidArgumentException(sprintf(
                        'Неизвестный путь папки "%s" для заметки "%s"',
                        $noteDefinition->folderPath,
                        $noteDefinition->key,
                    ));
                }
                $note->setFolder($folder);
            }

            foreach ($noteDefinition->tags as $tagName) {
                $tag = $tagsByName[$tagName] ?? null;
                if ($tag === null) {
                    throw new \InvalidArgumentException(sprintf(
                        'Неизвестный тег "%s" для заметки "%s"',
                        $tagName,
                        $noteDefinition->key,
                    ));
                }
                $note->addTag($tag);
            }

            $createdAt = $this->applyOffset($now, $noteDefinition->updatedAtOffset);
            $note->setCreatedAt($createdAt);
            $note->setUpdatedAt($createdAt);

            $this->entityManager->persist($note);
            $notesByKey[$noteDefinition->key] = $note;
        }

        $this->entityManager->flush();

        foreach ($definition->notes as $noteDefinition) {
            $note = $notesByKey[$noteDefinition->key];
            $resolvedContent = DemoLinkPlaceholderResolver::resolve($noteDefinition->content, $notesByKey);
            $note->setContent($resolvedContent);
        }

        $this->entityManager->flush();

        foreach ($notesByKey as $note) {
            $this->noteLinkSyncService->syncFromContent($note);
        }

        $versionCount = 0;

        foreach ($definition->notes as $noteDefinition) {
            if ($noteDefinition->versions === []) {
                continue;
            }

            $note = $notesByKey[$noteDefinition->key];
            $versions = $noteDefinition->versions;
            usort(
                $versions,
                static fn (DemoVersionDefinition $a, DemoVersionDefinition $b): int =>
                    strtotime($a->createdAtOffset) <=> strtotime($b->createdAtOffset),
            );

            foreach ($versions as $versionDefinition) {
                $version = new NoteVersion();
                $version->setNote($note);
                $version->setTitle($versionDefinition->title);
                $version->setContent(
                    DemoLinkPlaceholderResolver::resolve($versionDefinition->content, $notesByKey),
                );
                $version->setCreatedAt($this->applyOffset($now, $versionDefinition->createdAtOffset));
                $note->addVersion($version);
                $this->entityManager->persist($version);
                ++$versionCount;
            }
        }

        $this->entityManager->flush();

        $linkCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(link.id)')
            ->from(NoteLink::class, 'link')
            ->innerJoin('link.sourceNote', 'note')
            ->where('note.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return new DemoSeedResult(
            user: $user,
            folderCount: count($foldersByPath),
            tagCount: count($tagsByName),
            noteCount: count($notesByKey),
            linkCount: $linkCount,
            versionCount: $versionCount,
            favoriteCount: $favoriteCount,
        );
    }

    /**
     * @param list<string> $folderPaths
     *
     * @return array<string, Folder>
     */
    private function createFolders(array $folderPaths, User $user): array
    {
        /** @var array<string, Folder> $foldersByPath */
        $foldersByPath = [];

        foreach ($folderPaths as $folderPath) {
            $segments = explode('/', $folderPath);
            $currentPath = '';

            foreach ($segments as $segment) {
                $parentPath = $currentPath;
                $currentPath = $currentPath === '' ? $segment : $currentPath.'/'.$segment;

                if (isset($foldersByPath[$currentPath])) {
                    continue;
                }

                $folder = new Folder();
                $folder->setUser($user);
                $folder->setName($segment);

                if ($parentPath !== '') {
                    $parent = $foldersByPath[$parentPath] ?? null;
                    if ($parent === null) {
                        throw new \InvalidArgumentException(sprintf('Не удалось создать папку "%s"', $currentPath));
                    }
                    $folder->setParent($parent);
                }

                $this->entityManager->persist($folder);
                $foldersByPath[$currentPath] = $folder;
            }
        }

        return $foldersByPath;
    }

    /**
     * @param list<string> $tagNames
     *
     * @return array<string, Tag>
     */
    private function createTags(array $tagNames, User $user): array
    {
        /** @var array<string, Tag> $tagsByName */
        $tagsByName = [];

        foreach ($tagNames as $tagName) {
            $tag = new Tag();
            $tag->setUser($user);
            $tag->setName($tagName);
            $this->entityManager->persist($tag);
            $tagsByName[$tagName] = $tag;
        }

        return $tagsByName;
    }

    private function applyOffset(\DateTimeImmutable $base, string $offset): \DateTimeImmutable
    {
        $timestamp = strtotime($offset, $base->getTimestamp());
        if ($timestamp === false) {
            throw new \InvalidArgumentException(sprintf('Некорректное смещение даты: %s', $offset));
        }

        return (new \DateTimeImmutable())->setTimestamp($timestamp);
    }
}
