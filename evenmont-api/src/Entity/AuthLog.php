<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class AuthLog
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true)
     */
    private $user;

    /**
     * @ORM\Column(type="datetime")
     */
    private $occurredAt;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private $ipAddress;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $userAgent;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $action;
}
