<?php
namespace App\Entity;


use App\Repository\CommentReportRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */

#[ORM\Entity(repositoryClass: CommentReportRepository::class)]
class CommentReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Comment::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Comment $comment = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $reporter = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: 'string', length: 10)]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $handledBy = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $handledAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    public function setComment(?Comment $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getReporter(): ?User
    {
        return $this->reporter;
    }

    public function setReporter(?User $reporter): self
    {
        $this->reporter = $reporter;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getHandledBy(): ?User
    {
        return $this->handledBy;
    }

    public function setHandledBy(?User $handledBy): self
    {
        $this->handledBy = $handledBy;
        return $this;
    }

    public function getHandledAt(): ?\DateTime
    {
        return $this->handledAt;
    }

    public function setHandledAt(?\DateTime $handledAt): self
    {
        $this->handledAt = $handledAt;
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
