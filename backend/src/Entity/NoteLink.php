<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use App\Repository\NoteLinkRepository;
use App\State\NoteLinkCollectionProvider;
use App\State\NoteLinkProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NoteLinkRepository::class)]
#[ORM\Table(name: 'note_links')]
#[ORM\UniqueConstraint(name: 'source_target_unique', columns: ['source_note_id', 'target_note_id'])]
#[ApiResource(
    operations: [
        new GetCollection(provider: NoteLinkCollectionProvider::class),
        new Get(),
        new Post(processor: NoteLinkProcessor::class),
        new Delete(processor: NoteLinkProcessor::class),
    ]
)]
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
}
