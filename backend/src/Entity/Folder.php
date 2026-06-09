<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\FolderRepository;
use App\State\FolderTreeProvider;
use App\State\FolderProcessor;
use App\State\FolderCollectionProvider;
use App\Validator\MaxFolderDepth;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: FolderRepository::class)]
#[ORM\Table(name: 'folders')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/folders/tree',
            provider: FolderTreeProvider::class,
            name: 'tree'
        ),
        new GetCollection(provider: FolderCollectionProvider::class),
        new Get(),
        new Post(processor: FolderProcessor::class),
        new Put(processor: FolderProcessor::class),
        new Delete(processor: FolderProcessor::class),
    ],
    normalizationContext: ['groups' => ['folder:read']],
    denormalizationContext: ['groups' => ['folder:write']]
)]
#[MaxFolderDepth(3)]
class Folder
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['folder:read', 'note:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'folders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Groups(['folder:read', 'folder:write'])]
    private ?self $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[Groups(['folder:read'])]
    private Collection $children;

    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'folder')]
    private Collection $notes;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Название папки обязательно')]
    #[Assert\Length(max: 255, maxMessage: 'Название не может превышать {{ limit }} символов')]
    #[Groups(['folder:read', 'folder:write', 'note:read'])]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['folder:read'])]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->notes = new ArrayCollection();
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }
        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }
        return $this;
    }

    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setFolder($this);
        }
        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getFolder() === $this) {
                $note->setFolder(null);
            }
        }
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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
}
