<?php
namespace App\Entity;


use App\Repository\TicketTypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */

#[ORM\Entity(repositoryClass: TicketTypeRepository::class)]
class TicketType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\Column(type: 'string', length: 120)]
    private ?string $name = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(type: 'string', length: 3)]
    private ?string $currency = null;

    #[ORM\Column(type: 'integer')]
    private ?int $quantityTotal = null;

    #[ORM\Column(type: 'integer')]
    private ?int $quantitySold = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $salesStartAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $salesEndAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getQuantityTotal(): ?int
    {
        return $this->quantityTotal;
    }

    public function setQuantityTotal(int $quantityTotal): self
    {
        $this->quantityTotal = $quantityTotal;
        return $this;
    }

    public function getQuantitySold(): ?int
    {
        return $this->quantitySold;
    }

    public function setQuantitySold(int $quantitySold): self
    {
        $this->quantitySold = $quantitySold;
        return $this;
    }

    public function getSalesStartAt(): ?\DateTime
    {
        return $this->salesStartAt;
    }

    public function setSalesStartAt(?\DateTime $salesStartAt): self
    {
        $this->salesStartAt = $salesStartAt;
        return $this;
    }

    public function getSalesEndAt(): ?\DateTime
    {
        return $this->salesEndAt;
    }

    public function setSalesEndAt(?\DateTime $salesEndAt): self
    {
        $this->salesEndAt = $salesEndAt;
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

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
