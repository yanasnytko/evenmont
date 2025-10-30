<?php
namespace App\Entity;


use App\Repository\TicketRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EventRegistration::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?EventRegistration $registration = null;

    #[ORM\ManyToOne(targetEntity: TicketType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?TicketType $ticketType = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $qrCode = null;

    #[ORM\Column(type: 'string', length: 10)]
    private ?string $status = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $issuedAt = null;
        public function getUser(): ?\App\Entity\User
        {
            return $this->registration ? $this->registration->getUser() : null;
        }

        public function setUser(?\App\Entity\User $user): self
        {
            if ($this->registration) {
                $this->registration->setUser($user);
            }
            return $this;
        }

        public function getEvent(): ?\App\Entity\Event
        {
            return $this->registration ? $this->registration->getEvent() : null;
        }

        public function setEvent(?\App\Entity\Event $event): self
        {
            if ($this->registration) {
                $this->registration->setEvent($event);
            }
            return $this;
        }

        public function getPurchaseDate(): ?\DateTime
        {
            return $this->issuedAt;
        }

        public function setPurchaseDate(\DateTime $purchaseDate): self
        {
            $this->issuedAt = $purchaseDate;
            return $this;
        }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRegistration(): ?EventRegistration
    {
        return $this->registration;
    }

    public function setRegistration(?EventRegistration $registration): self
    {
        $this->registration = $registration;
        return $this;
    }

    public function getTicketType(): ?TicketType
    {
        return $this->ticketType;
    }

    public function setTicketType(?TicketType $ticketType): self
    {
        $this->ticketType = $ticketType;
        return $this;
    }

    public function getQrCode(): ?string
    {
        return $this->qrCode;
    }

    public function setQrCode(?string $qrCode): self
    {
        $this->qrCode = $qrCode;
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

    public function getIssuedAt(): ?\DateTime
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(\DateTime $issuedAt): self
    {
        $this->issuedAt = $issuedAt;
        return $this;
    }
}
