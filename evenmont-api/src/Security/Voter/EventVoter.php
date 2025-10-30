<?php

namespace App\Security\Voter;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EventVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE], true)
            && $subject instanceof Event;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) return false;
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) return true;

        /** @var Event $event */
        $event = $subject;

        // doit être organizer ET propriétaire
        $isOrganizer = in_array('ROLE_ORGANIZER', $user->getRoles(), true);
        $owner = $event->getOrganizer();
        $isOwner = $owner && $owner->getId() === $user->getId();

        return $isOrganizer && $isOwner;
    }
}
