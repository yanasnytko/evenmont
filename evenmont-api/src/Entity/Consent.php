<?php
namespace App\Entity;


use App\Repository\ConsentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */

#[ORM\Entity(repositoryClass: ConsentRepository::class)]
class Consent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $type = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $granted = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $version = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $grantedAt = null;
        public function getGivenAt(): ?\DateTime
        {
            return $this->grantedAt;
        }

        public function setGivenAt(\DateTime $givenAt): self
        {
            $this->grantedAt = $givenAt;
            return $this;
        }

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function isGranted(): ?bool
    {
        return $this->granted;
    }

    public function setGranted(bool $granted): self
    {
        $this->granted = $granted;
        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getGrantedAt(): ?\DateTime
    {
        return $this->grantedAt;
    }

    public function setGrantedAt(\DateTime $grantedAt): self
    {
        $this->grantedAt = $grantedAt;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }
}
