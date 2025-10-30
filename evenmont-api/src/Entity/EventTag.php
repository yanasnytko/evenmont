<?php
namespace App\Entity;

/**
 * @ORM\Entity
 */
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_tag')]
#[ORM\UniqueConstraint(name: 'UNIQ_EVENT_TAG', columns: ['event_id', 'tag_id'])]
class EventTag
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: "eventTags")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Tag::class, inversedBy: "eventTags")]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tag $tag = null;

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): self
    {
        $this->tag = $tag;
        return $this;
    }
}
