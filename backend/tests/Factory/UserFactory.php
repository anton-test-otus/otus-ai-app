<?php

namespace App\Tests\Factory;

use App\Entity\Folder;
use App\Entity\Note;
use App\Entity\NoteVersion;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFactory
{
    public const PASSWORD = 'password';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function createUser(string $email, array $roles = []): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::PASSWORD));
        if ($roles !== []) {
            $user->setRoles($roles);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function createFolder(User $user, string $name = 'Folder', ?Folder $parent = null): Folder
    {
        $user = $this->resolveUser($user);
        $folder = new Folder();
        $folder->setUser($user);
        $folder->setName($name);
        $folder->setParent($parent);

        $this->entityManager->persist($folder);
        $this->entityManager->flush();

        return $folder;
    }

    public function createTag(User $user, string $name = 'tag'): Tag
    {
        $user = $this->resolveUser($user);
        $tag = new Tag();
        $tag->setUser($user);
        $tag->setName($name);

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $tag;
    }

    public function createNote(
        User $user,
        string $title = 'Note',
        string $content = 'content',
        ?Folder $folder = null,
    ): Note {
        $user = $this->resolveUser($user);
        $note = new Note();
        $note->setUser($user);
        $note->setTitle($title);
        $note->setContent($content);
        $note->setFolder($folder);

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        return $note;
    }

    public function createNoteVersion(Note $note, ?string $content = null, ?string $title = null): NoteVersion
    {
        if (!$this->entityManager->contains($note)) {
            $note = $this->entityManager->getReference(Note::class, $note->getId());
        }
        $version = new NoteVersion();
        $version->setNote($note);
        $version->setContent($content ?? $note->getContent() ?? '');
        $version->setTitle($title ?? $note->getTitle() ?? 'Note');

        $this->entityManager->persist($version);
        $this->entityManager->flush();

        return $version;
    }

    public function softDeleteNote(Note $note): void
    {
        $note->setDeletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    private function resolveUser(User $user): User
    {
        if ($this->entityManager->contains($user)) {
            return $user;
        }

        return $this->entityManager->getReference(User::class, $user->getId());
    }
}
