<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\QueryParameter;
use App\Repository\TagRepository;
use App\State\TagProcessor;
use App\State\TagCollectionProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'tags')]
#[ORM\UniqueConstraint(name: 'user_tag_unique', columns: ['user_id', 'name'])]
#[ApiResource(
    operations: [
        new GetCollection(
            provider: TagCollectionProvider::class,
            parameters: [
                'folderId' => new QueryParameter(description: 'Теги заметок в указанной папке'),
                'tags' => new QueryParameter(description: 'Уже выбранные теги для сужения списка (логика И)'),
            ],
        ),
        new Get(),
        new Post(processor: TagProcessor::class),
        new Put(processor: TagProcessor::class),
        new Delete(processor: TagProcessor::class),
    ],
    normalizationContext: ['groups' => ['tag:read']],
    denormalizationContext: ['groups' => ['tag:write']]
)]
class Tag
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['tag:read', 'note:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tags')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Название тега обязательно')]
    #[Assert\Length(max: 50, maxMessage: 'Название тега не может превышать {{ limit }} символов')]
    #[Groups(['tag:read', 'tag:write', 'note:read'])]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Note::class, mappedBy: 'tags')]
    private Collection $notes;

    public function __construct()
    {
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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
            $note->addTag($this);
        }
        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            $note->removeTag($this);
        }
        return $this;
    }
}
