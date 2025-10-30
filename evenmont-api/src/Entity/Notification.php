<?php
namespace App\Entity;


use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Event $event = null;

    #[ORM\Column(type: 'string', length: 10)]
    private ?string $type = null;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $templateKey = null;

    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private $dataJson = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $scheduledAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $sentAt = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $channelStatus = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $seenAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getTemplateKey(): ?string
    {
        return $this->templateKey;
    }

    public function setTemplateKey(?string $templateKey): self
    {
        $this->templateKey = $templateKey;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getDataJson()
    {
        return $this->dataJson;
    }

    public function setDataJson($dataJson): self
    {
        $this->dataJson = $dataJson;
        return $this;
    }

    public function getScheduledAt(): ?\DateTime
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTime $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;
        return $this;
    }

    public function getSentAt(): ?\DateTime
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTime $sentAt): self
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getChannelStatus(): ?string
    {
        return $this->channelStatus;
    }

    public function setChannelStatus(?string $channelStatus): self
    {
        $this->channelStatus = $channelStatus;
        return $this;
    }

    public function getSeenAt(): ?\DateTime
    {
        return $this->seenAt;
    }

    public function setSeenAt(?\DateTime $seenAt): self
    {
        $this->seenAt = $seenAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
