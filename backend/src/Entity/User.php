<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Settings\UserSettingOptions;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['user:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Email обязателен')]
    #[Assert\Email(message: 'Некорректный email')]
    #[Groups(['user:read', 'user:write'])]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank(message: 'Пароль обязателен', groups: ['user:create'])]
    #[Assert\Length(min: 6, minMessage: 'Пароль должен содержать минимум {{ limit }} символов')]
    #[Groups(['user:write'])]
    private ?string $plainPassword = null;

    #[ORM\Column]
    #[Groups(['user:read', 'user:write'])]
    private bool $isActive = true;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Choice(choices: UserSettingOptions::ALLOWED_AUTOSAVE_DELAY_SECONDS)]
    private ?int $autosaveDelaySeconds = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Choice(choices: UserSettingOptions::ALLOWED_VERSION_CONSOLIDATION_WINDOW_MINUTES)]
    private ?int $versionConsolidationWindowMinutes = null;

    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $notes;

    #[ORM\OneToMany(targetEntity: Folder::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $folders;

    #[ORM\OneToMany(targetEntity: Tag::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $tags;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->folders = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
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

    public function getAutosaveDelaySeconds(): ?int
    {
        return $this->autosaveDelaySeconds;
    }

    public function setAutosaveDelaySeconds(?int $autosaveDelaySeconds): static
    {
        $this->autosaveDelaySeconds = $autosaveDelaySeconds;
        return $this;
    }

    public function getVersionConsolidationWindowMinutes(): ?int
    {
        return $this->versionConsolidationWindowMinutes;
    }

    public function setVersionConsolidationWindowMinutes(?int $versionConsolidationWindowMinutes): static
    {
        $this->versionConsolidationWindowMinutes = $versionConsolidationWindowMinutes;
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
            $note->setUser($this);
        }
        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getUser() === $this) {
                $note->setUser(null);
            }
        }
        return $this;
    }

    public function getFolders(): Collection
    {
        return $this->folders;
    }

    public function addFolder(Folder $folder): static
    {
        if (!$this->folders->contains($folder)) {
            $this->folders->add($folder);
            $folder->setUser($this);
        }
        return $this;
    }

    public function removeFolder(Folder $folder): static
    {
        if ($this->folders->removeElement($folder)) {
            if ($folder->getUser() === $this) {
                $folder->setUser(null);
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
            $tag->setUser($this);
        }
        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        if ($this->tags->removeElement($tag)) {
            if ($tag->getUser() === $this) {
                $tag->setUser(null);
            }
        }
        return $this;
    }
}
