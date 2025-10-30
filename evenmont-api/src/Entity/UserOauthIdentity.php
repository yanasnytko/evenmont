<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class UserOauthIdentity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $provider;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $providerUserId;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $accessToken;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $refreshToken;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $expiresAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;
}
