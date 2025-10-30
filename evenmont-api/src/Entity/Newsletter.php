<?php
namespace App\Entity;


use App\Repository\NewsletterRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 */

#[ORM\Entity(repositoryClass: NewsletterRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Déjà inscrit.')]
class Newsletter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: true)]
    private ?string $email = null;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Language $language = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $subscribedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $unsubscribedAt = null;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    public function setLanguage(?Language $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getSubscribedAt(): ?\DateTime
    {
        return $this->subscribedAt;
    }

    public function setSubscribedAt(\DateTime $subscribedAt): self
    {
        $this->subscribedAt = $subscribedAt;
        return $this;
    }

    public function getUnsubscribedAt(): ?\DateTime
    {
        return $this->unsubscribedAt;
    }

    public function setUnsubscribedAt(?\DateTime $unsubscribedAt): self
    {
        $this->unsubscribedAt = $unsubscribedAt;
        return $this;
    }
}
