<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Event;
use App\Entity\Tag;
use App\Entity\EventTag;
use App\Entity\EventRegistration;

// use App\Entity\TicketType;
// use App\Entity\Ticket;
// use App\Entity\Payment;
// use App\Entity\Comment;
// use App\Entity\Newsletter;
// use App\Entity\Consent;
// use App\Entity\Favorite;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        mt_srand(42); // déterministe

        // === USERS ===========================================================
        // 1 admin
        $admin = (new User())
            ->setEmail('admin@evenmont.com')
            ->setFirstName('Admin')
            ->setLastName('Root')
            ->setRoles(['ROLE_ADMIN'])
            ->setCreatedAt(new \DateTimeImmutable());
        $admin->setPassword($this->hasher->hashPassword($admin, 'adminpass'));
        $manager->persist($admin);

        // 2 utilisateurs simples
        $userA = (new User())
            ->setEmail('alice@example.com')
            ->setFirstName('Alice')->setLastName('Durand')
            ->setRoles(['ROLE_USER'])
            ->setCreatedAt(new \DateTimeImmutable());
        $userA->setPassword($this->hasher->hashPassword($userA, 'password'));
        $manager->persist($userA);

        $userB = (new User())
            ->setEmail('bob@example.com')
            ->setFirstName('Bob')->setLastName('Martin')
            ->setRoles(['ROLE_USER'])
            ->setCreatedAt(new \DateTimeImmutable());
        $userB->setPassword($this->hasher->hashPassword($userB, 'password'));
        $manager->persist($userB);

        // 5 organisateurs
        $organizers = [];
        for ($i = 1; $i <= 5; $i++) {
            $u = (new User())
                ->setEmail(sprintf('orga%d@evenmont.com', $i))
                ->setFirstName('Orga' . $i)
                ->setLastName('Montagne')
                ->setRoles(['ROLE_ORGANIZER'])
                ->setCreatedAt(new \DateTimeImmutable());
            $u->setPassword($this->hasher->hashPassword($u, 'password'));
            $manager->persist($u);
            $organizers[] = $u;
        }

        // === TAGS / CATEGORIES ==============================================
        $tagData = [
            ['Conférence', 'conference'],
            ['Atelier', 'atelier'],
            ['Concert', 'concert'],
            ['Brunch', 'brunch'],
            ['Rando', 'rando'],
        ];
        $tags = [];
        foreach ($tagData as [$name, $slug]) {
            $t = (new Tag())->setName($name)->setSlug($slug);
            $manager->persist($t);
            $tags[] = $t;
        }

        // === EVENTS ==========================================================
        $cities = ['Chamonix', 'Grenoble', 'Annecy', 'Lyon', 'Gap', 'Briançon', 'Nice', 'Marseille'];
        $titles = [
            'Sunrise Hike & Brunch',
            'Meetup Dev Symfony',
            'Concert acoustique au refuge',
            'Atelier Vue.js',
            'Conférence Climat & Montagne',
            'Trail découverte',
            'Yoga au sommet',
            'Atelier photo en altitude',
            'Nuit des étoiles',
            'Conférence sécurité avalanche',
            'Brunch des organisateurs',
            'Afterwork networking',
        ];

        $events = [];
        $today = new \DateTimeImmutable('today 10:00:00');
        $n = max(12, count($titles)); // au moins 12 évènements

        for ($i = 0; $i < $n; $i++) {
            $org = $organizers[$i % count($organizers)];
            $city = $cities[$i % count($cities)];
            $title = $titles[$i % count($titles)];

            // dates futures échelonnées
            $start = $today->modify(sprintf('+%d days', 3 * $i));
            $end   = $start->modify('+4 hours');

            $e = (new Event())
                ->setTitle($title)
                ->setDescription("Événement \"$title\" à $city.\nProgramme, intervenants et networking au rendez-vous.")
                ->setCity($city)
                ->setStartAt($start)
                ->setEndAt($end)
                ->setCoverUrl(sprintf('/img/demo%d.jpg', ($i % 4) + 1))
                ->setOrganizer($org);

            $manager->persist($e);
            $events[] = $e;

            // lier 1 à 2 tags
            $tag1 = $tags[$i % count($tags)];
            $manager->persist((new EventTag())->setEvent($e)->setTag($tag1));
            if ($i % 2 === 0) {
                $tag2 = $tags[($i + 2) % count($tags)];
                if ($tag2 !== $tag1) {
                    $manager->persist((new EventTag())->setEvent($e)->setTag($tag2));
                }
            }
        }

        // === REGISTRATIONS (quelques-unes pour tester) =======================
        // userA inscrit à 2 events, userB à 1
        if (!empty($events)) {
            $r1 = (new EventRegistration())
                ->setEvent($events[0])
                ->setUser($userA)
                ->setStatus('confirmed')
                ->setCreatedAt($today->modify('+1 day'));
            $manager->persist($r1);

            if (isset($events[1])) {
                $r2 = (new EventRegistration())
                    ->setEvent($events[1])
                    ->setUser($userA)
                    ->setStatus('pending')
                    ->setCreatedAt($today->modify('+2 days'));
                $manager->persist($r2);
            }

            if (isset($events[2])) {
                $r3 = (new EventRegistration())
                    ->setEvent($events[2])
                    ->setUser($userB)
                    ->setStatus('confirmed')
                    ->setCreatedAt($today->modify('+3 days'));
                $manager->persist($r3);
            }
        }

        // === (OPTIONNEL) autres entités — tu peux réactiver si tu veux ======
        // // Exemple d’un ticket type rapide
        // if (isset($events[0])) {
        //     $tt = (new TicketType())
        //         ->setName('Standard')->setPrice('30.00')->setCurrency('EUR')
        //         ->setEvent($events[0])->setQuantityTotal(100)->setQuantitySold(0)
        //         ->setCreatedAt(new \DateTimeImmutable())
        //         ->setSalesStartAt($today)
        //         ->setSalesEndAt($events[0]->getEndAt() ?? $today->modify('+1 day'));
        //     $manager->persist($tt);
        // }

        $manager->flush();
    }
}
