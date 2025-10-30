<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payment')]
#[ORM\Index(columns: ['mollie_id'], name: 'idx_payment_mollie')]
#[ORM\Index(columns: ['transaction_ref'], name: 'idx_payment_txref')]
class Payment
{
    public const STATUS_OPEN     = 'open';
    public const STATUS_PAID     = 'paid';
    public const STATUS_FAILED   = 'failed';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_EXPIRED  = 'expired';
    public const STATUS_REFUNDED = 'refunded';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    // tu peux laisser bigint si tu veux, sinon 'integer'
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    // OPTION A: lien direct User + Event (pas Registration)
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    // Identifiants PSP (Mollie)
    #[ORM\Column(name: 'mollie_id', type: 'string', length: 40, nullable: true)]
    private ?string $mollieId = null;

    #[ORM\Column(name: 'transaction_ref', type: 'string', length: 191, nullable: true)]
    private ?string $transactionRef = null;

    #[ORM\Column(name: 'checkout_url', type: 'string', length: 1024, nullable: true)]
    private ?string $checkoutUrl = null;

    // Données complémentaires (ex: metadata renvoyée par ton code)
    #[ORM\Column(name: 'metadata', type: 'json', nullable: true)]
    private ?array $metadata = null;

    // Paiement
    #[ORM\Column(type: 'string', length: 20)]
    private string $provider = 'mollie';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amount = '0.00'; // decimal => string en PHP (normal)

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency = 'EUR';

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_OPEN;

    // Dates
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ==== Getters/Setters ====

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

    public function getMollieId(): ?string
    {
        return $this->mollieId;
    }
    public function setMollieId(?string $mollieId): self
    {
        $this->mollieId = $mollieId;
        return $this;
    }

    public function getTransactionRef(): ?string
    {
        return $this->transactionRef;
    }
    public function setTransactionRef(?string $transactionRef): self
    {
        $this->transactionRef = $transactionRef;
        return $this;
    }

    public function getCheckoutUrl(): ?string
    {
        return $this->checkoutUrl;
    }
    public function setCheckoutUrl(?string $checkoutUrl): self
    {
        $this->checkoutUrl = $checkoutUrl;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }
    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
    public function setProvider(string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }
    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->touch();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }
    public function setPaidAt(?\DateTimeImmutable $paidAt): self
    {
        $this->paidAt = $paidAt;
        $this->touch();
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // helpers
    public function markPaid(): void
    {
        $this->status = self::STATUS_PAID;
        $this->paidAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
