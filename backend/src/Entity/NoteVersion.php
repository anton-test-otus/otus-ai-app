<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Repository\NoteVersionRepository;
use App\State\NoteVersionsByNoteProvider;
use App\State\RestoreVersionProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NoteVersionRepository::class)]
#[ORM\Table(name: 'note_versions')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/notes/{noteId}/versions',
            uriVariables: [
                'noteId' => new Link(fromClass: Note::class, toProperty: 'note'),
            ],
            provider: NoteVersionsByNoteProvider::class,
            name: 'note_versions_by_note'
        ),
        new Get(),
        new Post(
            uriTemplate: '/notes/{noteId}/versions/{id}/restore',
            uriVariables: [
                'noteId' => new Link(fromClass: Note::class, toProperty: 'note'),
                'id' => new Link(fromClass: NoteVersion::class),
            ],
            processor: RestoreVersionProcessor::class,
            name: 'restore_version'
        ),
    ],
    normalizationContext: ['groups' => ['version:read']]
)]
class NoteVersion
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['version:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Note::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Note $note = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['version:read'])]
    private ?string $content = null;

    #[ORM\Column(length: 255)]
    #[Groups(['version:read'])]
    private ?string $title = null;

    #[ORM\Column]
    #[Groups(['version:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getNote(): ?Note
    {
        return $this->note;
    }

    public function setNote(?Note $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
