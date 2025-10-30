<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity]
class RefreshToken extends BaseRefreshToken
{
    // Ne rien ajouter : on hérite des champs (refreshToken, valid, username)
}
