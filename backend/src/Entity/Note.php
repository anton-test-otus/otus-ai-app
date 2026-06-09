<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\NoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\Table(name: 'notes')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['note:read']],
    denormalizationContext: ['groups' => ['note:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 20
)]
class Note
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['note:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Folder::class, inversedBy: 'notes')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups(['note:read', 'note:write'])]
    private ?Folder $folder = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Заголовок обязателен')]
    #[Assert\Length(max: 255, maxMessage: 'Заголовок не может превышать {{ limit }} символов')]
    #[Groups(['note:read', 'note:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Содержимое не может быть пустым')]
    #[Groups(['note:read', 'note:write'])]
    private ?string $content = null;

    #[ORM\Column]
    #[Groups(['note:read', 'note:write'])]
    private int $position = 0;

    #[ORM\Column]
    #[Groups(['note:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['note:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['note:read'])]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\OneToMany(targetEntity: NoteVersion::class, mappedBy: 'note', orphanRemoval: true)]
    private Collection $versions;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'notes')]
    #[ORM\JoinTable(name: 'note_tags')]
    #[Groups(['note:read', 'note:write'])]
    private Collection $tags;

    #[ORM\OneToMany(targetEntity: NoteLink::class, mappedBy: 'sourceNote', orphanRemoval: true)]
    private Collection $outgoingLinks;

    #[ORM\OneToMany(targetEntity: NoteLink::class, mappedBy: 'targetNote', orphanRemoval: true)]
    private Collection $incomingLinks;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->outgoingLinks = new ArrayCollection();
        $this->incomingLinks = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): static
    {
        $this->folder = $folder;
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(NoteVersion $version): static
    {
        if (!$this->versions->contains($version)) {
            $this->versions->add($version);
            $version->setNote($this);
        }
        return $this;
    }

    public function removeVersion(NoteVersion $version): static
    {
        if ($this->versions->removeElement($version)) {
            if ($version->getNote() === $this) {
                $version->setNote(null);
            }
        }
        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    public function getOutgoingLinks(): Collection
    {
        return $this->outgoingLinks;
    }

    public function addOutgoingLink(NoteLink $link): static
    {
        if (!$this->outgoingLinks->contains($link)) {
            $this->outgoingLinks->add($link);
            $link->setSourceNote($this);
        }
        return $this;
    }

    public function removeOutgoingLink(NoteLink $link): static
    {
        if ($this->outgoingLinks->removeElement($link)) {
            if ($link->getSourceNote() === $this) {
                $link->setSourceNote(null);
            }
        }
        return $this;
    }

    public function getIncomingLinks(): Collection
    {
        return $this->incomingLinks;
    }

    public function addIncomingLink(NoteLink $link): static
    {
        if (!$this->incomingLinks->contains($link)) {
            $this->incomingLinks->add($link);
            $link->setTargetNote($this);
        }
        return $this;
    }

    public function removeIncomingLink(NoteLink $link): static
    {
        if ($this->incomingLinks->removeElement($link)) {
            if ($link->getTargetNote() === $this) {
                $link->setTargetNote(null);
            }
        }
        return $this;
    }
}
