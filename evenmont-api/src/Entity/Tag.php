<?php
namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug existe déjà.')]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 120, unique: true)]
    private ?string $slug = null;

    /**
     * @var Collection<int, EventTag>
     */
    #[ORM\OneToMany(targetEntity: EventTag::class, mappedBy: 'tag')]
    private Collection $eventTags;

    public function __construct()
    {
        $this->eventTags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return Collection<int, EventTag>
     */
    public function getEventTags(): Collection
    {
        return $this->eventTags;
    }

    public function addEventTag(EventTag $eventTag): static
    {
        if (!$this->eventTags->contains($eventTag)) {
            $this->eventTags->add($eventTag);
            $eventTag->setTag($this);
        }
        return $this;
    }

    public function removeEventTag(EventTag $eventTag): static
    {
        if ($this->eventTags->removeElement($eventTag)) {
            if ($eventTag->getTag() === $this) {
                $eventTag->setTag(null);
            }
        }
        return $this;
    }
}
