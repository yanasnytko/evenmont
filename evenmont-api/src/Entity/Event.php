<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\EventTag;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 191)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $endAt = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $city = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $capacity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(length: 20, options: ['default' => 'published'])]
    private string $status = 'published';

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $organizer = null;

    /**
     * @var Collection<int, EventRegistration>
     */
    #[ORM\OneToMany(targetEntity: EventRegistration::class, mappedBy: 'event')]
    private Collection $eventRegistrations;

    #[ORM\OneToMany(targetEntity: EventTag::class, mappedBy: 'event', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $eventTags;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverUrl = null;

    public function __construct()
    {
        $this->eventRegistrations = new ArrayCollection();
        $this->eventTags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(?\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;
        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

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

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }
    public function setCapacity(?int $capacity): self
    {
        $this->capacity = $capacity;
        return $this;
    }


    #[ORM\PrePersist]
    public function stampCreatedAt(): void
    {
        if (!$this->createdAt) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        $this->organizer = $organizer;

        return $this;
    }

    /**
     * @return Collection<int, EventRegistration>
     */
    public function getEventRegistrations(): Collection
    {
        return $this->eventRegistrations;
    }
    public function getRegistrationsCount(): int
    {
        return $this->eventRegistrations->count();
    }

    public function addEventRegistration(EventRegistration $eventRegistration): static
    {
        if (!$this->eventRegistrations->contains($eventRegistration)) {
            $this->eventRegistrations->add($eventRegistration);
            $eventRegistration->setEvent($this);
        }

        return $this;
    }

    public function removeEventRegistration(EventRegistration $eventRegistration): static
    {
        if ($this->eventRegistrations->removeElement($eventRegistration)) {
            // set the owning side to null (unless already changed)
            if ($eventRegistration->getEvent() === $this) {
                $eventRegistration->setEvent(null);
            }
        }

        return $this;
    }

    public function getEventTags(): Collection
    {
        return $this->eventTags;
    }

    public function addEventTag(EventTag $eventTag): self
    {
        if (!$this->eventTags->contains($eventTag)) {
            $this->eventTags->add($eventTag);
            $eventTag->setEvent($this);
        }
        return $this;
    }

    public function removeEventTag(EventTag $eventTag): self
    {
        if ($this->eventTags->removeElement($eventTag)) {
            if ($eventTag->getEvent() === $this) {
                $eventTag->setEvent(null);
            }
        }
        return $this;
    }

    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }

    public function setCoverUrl(?string $coverUrl): static
    {
        $this->coverUrl = $coverUrl;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price; // ex "12.00" ou null
    }

    public function setPrice(?string $price): self
    {
        // accepte null ou "12.00" (utilisé par Doctrine)
        $this->price = $price;
        return $this;
    }

    /** helpers optionnels */
    public function getPriceFloat(): ?float
    {
        return $this->price !== null ? (float)$this->price : null;
    }
    public function isFree(): bool
    {
        return $this->price === null || (float)$this->price <= 0;
    }

    public function isPast(): bool
    {
        return $this->getStartAt() && $this->getStartAt() < new \DateTimeImmutable('now');
    }
}
