<?php

namespace App\Entity;

use App\Repository\NoteLinkRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NoteLinkRepository::class)]
#[ORM\Table(name: 'note_links')]
#[ORM\UniqueConstraint(name: 'source_target_unique', columns: ['source_note_id', 'target_note_id'])]
class NoteLink
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Note::class, inversedBy: 'outgoingLinks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Note $sourceNote = null;

    #[ORM\ManyToOne(targetEntity: Note::class, inversedBy: 'incomingLinks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Note $targetNote = null;

    /** @var array<int, string|null> */
    #[ORM\Column(type: 'json')]
    private array $aliases = [];

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getSourceNote(): ?Note
    {
        return $this->sourceNote;
    }

    public function setSourceNote(?Note $sourceNote): static
    {
        $this->sourceNote = $sourceNote;
        return $this;
    }

    public function getTargetNote(): ?Note
    {
        return $this->targetNote;
    }

    public function setTargetNote(?Note $targetNote): static
    {
        $this->targetNote = $targetNote;
        return $this;
    }

    /**
     * @return array<int, string|null>
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @param array<int, string|null> $aliases
     */
    public function setAliases(array $aliases): static
    {
        $this->aliases = $aliases;

        return $this;
    }
}
