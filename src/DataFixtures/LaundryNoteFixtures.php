<?php

namespace App\DataFixtures;

use App\Entity\Laundry;
use App\Entity\LaundryNote;
use App\Entity\User;
use App\Enum\LaundryStatusEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

class LaundryNoteFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user1 = $this->findOneOrFail($manager, User::class, ['email' => 'marie.dupont@example.com'], 'marie.dupont@example.com');
        $user2 = $this->findOneOrFail($manager, User::class, ['email' => 'jean.martin@example.com'], 'jean.martin@example.com');

        $approvedLaundries = $manager->getRepository(Laundry::class)->findBy(['status' => LaundryStatusEnum::APPROVED]);

        // Chaque entrée correspond à une laverie (par index). Max 2 notes par laverie : une par user.
        // [user, rating, ratedAt, comment|null, commentedAt|null, response|null, respondedAt|null]
        $notesPerLaundry = [
            // index 0 — Laverie Batignolles
            [
                [$user1, 5, '2026-04-05 18:00:00', 'Machines impeccables et toujours disponibles, je reviendrai !', '2026-04-05 18:03:00', 'Merci beaucoup pour ce retour positif, a tres bientot !', '2026-04-06 09:00:00'],
                [$user2, 4, '2026-04-06 10:30:00', 'Tres propre, bon rapport qualite-prix.', '2026-04-06 10:32:00', null, null],
            ],
            // index 1 — Paris Sud Lavage Pro
            [
                [$user1, 3, '2026-04-07 14:15:00', null, null, null, null],
                [$user2, 5, '2026-04-08 11:00:00', 'Parfait, les seche-linges sont puissants et le lieu est agreable.', '2026-04-08 11:05:00', 'Nous sommes ravis que vous appreciez nos installations !', '2026-04-09 08:30:00'],
            ],
            // index 2 — Belleville Clean Station
            [
                [$user1, 2, '2026-04-09 16:45:00', 'Une machine etait hors service lors de mon passage.', '2026-04-09 16:48:00', null, null],
                [$user2, 4, '2026-04-10 09:20:00', null, null, null, null],
            ],
            // index 3 — Latin Quarter Wash
            [
                [$user1, 5, '2026-04-11 17:30:00', 'Excellent service, machines rapides et endroit bien entretenu.', '2026-04-11 17:33:00', 'Merci pour votre avis, nous faisons de notre mieux !', '2026-04-12 10:00:00'],
                [$user2, 3, '2026-04-12 13:00:00', null, null, null, null],
            ],
            // index 4 — Montmartre Lavage Express
            [
                [$user1, 4, '2026-04-13 09:00:00', 'Bonne laverie, seche-linge un peu lent mais propre.', '2026-04-13 09:04:00', null, null],
                [$user2, 5, '2026-04-13 15:30:00', 'Je suis tres satisfaite, je recommande vivement !', '2026-04-13 15:33:00', 'Merci, a bientot !', '2026-04-14 08:00:00'],
            ],
            // index 5 — Compiegne Wash Center
            [
                [$user1, 3, '2026-04-14 10:00:00', null, null, null, null],
                [$user2, 4, '2026-04-14 17:00:00', 'Correct, rien a redire sur la proprete.', '2026-04-14 17:02:00', null, null],
            ],
            // index 6 — Beauvais Lavage Plus
            [
                [$user1, 5, '2026-04-15 08:30:00', 'Super laverie en centre-ville, machines toujours libres.', '2026-04-15 08:34:00', 'Merci de votre confiance !', '2026-04-15 11:00:00'],
                [$user2, 2, '2026-04-15 19:00:00', null, null, null, null],
            ],
            // index 7 — Creil Laundry Hub
            [
                [$user1, 4, '2026-04-16 09:15:00', 'Bonne capacite de machines, tarifs raisonnables.', '2026-04-16 09:18:00', null, null],
                [$user2, 5, '2026-04-16 14:00:00', 'Tres agreable, je reviendrai regulierement.', '2026-04-16 14:03:00', 'Avec plaisir, merci !', '2026-04-17 09:00:00'],
            ],
            // index 8 — Senlis Lavage Minute
            [
                [$user1, 3, '2026-04-17 11:00:00', null, null, null, null],
                [$user2, 3, '2026-04-17 16:30:00', 'Correct mais manque de place pour attendre.', '2026-04-17 16:32:00', null, null],
            ],
            // index 9 — Chantilly Press and Wash
            [
                [$user1, 5, '2026-04-18 08:00:00', 'Calme, propre, machines en parfait etat.', '2026-04-18 08:04:00', 'Merci pour ce beau commentaire !', '2026-04-18 10:30:00'],
                [$user2, 4, '2026-04-18 13:00:00', null, null, null, null],
            ],
            // index 10 — Senlis Clean Express
            [
                [$user1, 2, '2026-04-19 09:00:00', 'Distributeur de lessive vide et une machine en panne.', '2026-04-19 09:03:00', 'Nous nous en excusons, cela a ete corrige depuis.', '2026-04-19 14:00:00'],
                [$user2, 4, '2026-04-19 18:00:00', null, null, null, null],
            ],
            // index 11 — Senlis Laverie du Centre
            [
                [$user1, 5, '2026-04-20 10:00:00', 'Tres spacieux avec une belle zone de pliage.', '2026-04-20 10:03:00', null, null],
                [$user2, 5, '2026-04-20 15:00:00', 'Le wifi est un vrai plus, merci !', '2026-04-20 15:02:00', 'Nous sommes heureux que ca vous soit utile !', '2026-04-21 09:00:00'],
            ],
            // index 12 — Senlis Wash and Go
            [
                [$user1, 4, '2026-04-21 08:30:00', null, null, null, null],
                [$user2, 3, '2026-04-21 14:00:00', 'Cycles rapides comme annonce, mais un peu bruyant.', '2026-04-21 14:05:00', null, null],
            ],
            // index 13 — Senlis Laundry Plus
            [
                [$user1, 5, '2026-04-22 09:00:00', 'Grand parking et machines de haute capacite, parfait.', '2026-04-22 09:04:00', 'Merci, bonne journee !', '2026-04-22 11:00:00'],
                [$user2, 4, '2026-04-22 17:00:00', null, null, null, null],
            ],
            // index 14 — Senlis Eco Lavage
            [
                [$user1, 4, '2026-04-23 10:00:00', 'Bonne initiative ecologique, lessive fournie appreciee.', '2026-04-23 10:04:00', null, null],
                [$user2, 5, '2026-04-23 15:30:00', 'Programmes basse temperature tres efficaces.', '2026-04-23 15:33:00', 'Merci de soutenir notre demarche eco !', '2026-04-24 08:30:00'],
            ],
            // index 15 — Senlis Pressing Moderne
            [
                [$user1, 3, '2026-04-24 09:00:00', null, null, null, null],
                [$user2, 4, '2026-04-24 14:00:00', 'Service pressing de qualite, je recommande.', '2026-04-24 14:03:00', null, null],
            ],
            // index 16 — Saint-Maximin Laverie Rapide
            [
                [$user1, 5, '2026-04-25 08:00:00', 'Paiement sans contact tres pratique, machines fiables.', '2026-04-25 08:03:00', 'Merci, a bientot !', '2026-04-25 10:00:00'],
                [$user2, 3, '2026-04-25 16:00:00', null, null, null, null],
            ],
            // index 17 — Orry Wash Service
            [
                [$user1, 4, '2026-04-26 09:30:00', 'Ouvert 7j/7, tres pratique pour les weekends.', '2026-04-26 09:33:00', null, null],
                [$user2, 5, '2026-04-26 14:00:00', 'Petite laverie tres conviviale, je reviendrai.', '2026-04-26 14:04:00', 'Merci, on vous attend !', '2026-04-27 08:00:00'],
            ],
            // index 18 — Chambly Clean Center
            [
                [$user1, 3, '2026-04-27 10:00:00', null, null, null, null],
                [$user2, 4, '2026-04-27 17:00:00', 'Eclairage LED agreable, distributeur bien approvisionne.', '2026-04-27 17:03:00', null, null],
            ],
            // index 19 — Clermont Lavage Central
            [
                [$user1, 5, '2026-04-28 08:30:00', 'Machines haute performance et wifi, que demander de plus ?', '2026-04-28 08:34:00', 'Merci beaucoup !', '2026-04-28 11:00:00'],
                [$user2, 4, '2026-04-28 15:00:00', null, null, null, null],
            ],
            // index 20 — Noyon Lav Express
            [
                [$user1, 2, '2026-04-29 09:00:00', 'Parking grand mais machines un peu vieilles.', '2026-04-29 09:04:00', 'Merci du retour, nous allons examiner ca.', '2026-04-29 14:00:00'],
                [$user2, 3, '2026-04-29 17:00:00', null, null, null, null],
            ],
            // index 21 — Pont-Sainte-Maxence Wash Hub
            [
                [$user1, 5, '2026-04-30 08:00:00', 'Espace attente tres confortable, machines silencieuses.', '2026-04-30 08:04:00', null, null],
                [$user2, 4, '2026-04-30 13:00:00', 'Basse consommation et bon entretien, bravo.', '2026-04-30 13:03:00', 'Merci de l attention !', '2026-04-30 16:00:00'],
            ],
            // index 22 — Compiegne Laverie Nord
            [
                [$user1, 4, '2026-05-01 09:00:00', null, null, null, null],
                [$user2, 5, '2026-05-01 15:00:00', 'Machines 20kg tres utiles pour la literie, top !', '2026-05-01 15:04:00', 'Merci, a bientot !', '2026-05-02 08:30:00'],
            ],
            // index 23 — Compiegne City Laundry
            [
                [$user1, 3, '2026-05-02 10:00:00', 'Service de depot interessant mais delai un peu long.', '2026-05-02 10:04:00', null, null],
                [$user2, 4, '2026-05-02 16:00:00', null, null, null, null],
            ],
            // index 24 — Meru Laverie Pratique
            [
                [$user1, 5, '2026-05-03 08:00:00', 'Horaires etendus tres pratiques pour les parents.', '2026-05-03 08:03:00', 'Merci, on essaie d etre accessibles !', '2026-05-03 10:00:00'],
                [$user2, 3, '2026-05-03 14:00:00', null, null, null, null],
            ],
            // index 25 — Crepy Wash Station
            [
                [$user1, 4, '2026-05-04 09:00:00', 'Ideal pour les navetteurs, proche de la gare.', '2026-05-04 09:03:00', null, null],
                [$user2, 5, '2026-05-04 18:00:00', 'Tres bien situe, machines en bon etat.', '2026-05-04 18:04:00', 'Merci du retour !', '2026-05-05 09:00:00'],
            ],
            // index 26 — Gouvieux Laverie du Marche
            [
                [$user1, 3, '2026-05-05 10:00:00', null, null, null, null],
                [$user2, 4, '2026-05-05 15:30:00', 'Face au marche, tres pratique pour combiner les courses.', '2026-05-05 15:34:00', null, null],
            ],
            // index 27 — Senlis Laverie de l Oise
            [
                [$user1, 5, '2026-05-06 08:30:00', 'Machines a double tambour impressionnantes, linge impeccable.', '2026-05-06 08:34:00', 'Merci, belle journee !', '2026-05-06 11:00:00'],
                [$user2, 4, '2026-05-06 14:00:00', null, null, null, null],
            ],
        ];

        foreach ($approvedLaundries as $index => $laundry) {
            $notes = $notesPerLaundry[$index] ?? [];
            foreach ($notes as [$user, $rating, $ratedAt, $comment, $commentedAt, $response, $respondedAt]) {
                $note = new LaundryNote();
                $note->setLaundry($laundry);
                $note->setUser($user);
                $note->setRating($rating);
                $note->setRatedAt(new \DateTime($ratedAt));

                if ($comment !== null) {
                    $note->setComment($comment);
                    $note->setCommentedAt(new \DateTime($commentedAt));
                }

                if ($response !== null) {
                    $note->setResponse($response);
                    $note->setRespondedAt(new \DateTime($respondedAt));
                }

                $manager->persist($note);
            }
        }

        $manager->flush();
    }

    private function findOneOrFail(ObjectManager $manager, string $entityClass, array $criteria, string $label): object
    {
        $entity = $manager->getRepository($entityClass)->findOneBy($criteria);

        if ($entity === null) {
            throw new RuntimeException(sprintf('%s not found for LaundryNoteFixtures.', $label));
        }

        return $entity;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            LaundryFixtures::class,
        ];
    }
}
