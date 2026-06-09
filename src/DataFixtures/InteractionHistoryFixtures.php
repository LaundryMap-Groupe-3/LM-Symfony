<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Laundry;
use App\Entity\Professional;
use App\Entity\LaundryInteractionHistory;
use App\Entity\ProfessionalInteractionHistory;
use App\Enum\InteractionActionEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class InteractionHistoryFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $admin1 = $manager->getRepository(Admin::class)->findOneBy(['email' => 'admin@example.com']);
        $admin2 = $manager->getRepository(Admin::class)->findOneBy(['email' => 'superadmin@example.com']);

        // Professionnels
        // professional1 (Laverie Martin) : APPROVED le 2025-11-15 → APPROVE direct, dossier complet
        // professional2 (Pressing Bernard) : APPROVED le 2026-01-25 → premier refus pour dossier incomplet, puis APPROVE après correction
        // professional3 (Lavomatic Durand) : PENDING → aucune action admin, pas d'historique
        $professional1 = $manager->getRepository(Professional::class)->findOneBy(['companyName' => 'Laverie Martin']);
        $professional2 = $manager->getRepository(Professional::class)->findOneBy(['companyName' => 'Pressing Bernard']);

        // Laveries
        // laundry1 (Bastille Express) : PENDING → soumise récemment, aucune action encore
        // laundry2 (Lille Clean Hub) : APPROVED → approuvée directement par admin2
        // Parmi les laveries APPROVED de la boucle : Laverie Batignolles et Latin Quarter Wash ont un historique
        $laundry2 = $manager->getRepository(Laundry::class)->findOneBy(['establishmentName' => 'Lille Clean Hub']);
        $laundryBatignolles = $manager->getRepository(Laundry::class)->findOneBy(['establishmentName' => 'Laverie Batignolles']);
        $laundryLatin = $manager->getRepository(Laundry::class)->findOneBy(['establishmentName' => 'Latin Quarter Wash']);

        // --- ProfessionalInteractionHistory ---

        // Laverie Martin : approuvé directement
        $h1 = new ProfessionalInteractionHistory();
        $h1->setAdmin($admin1);
        $h1->setProfessional($professional1);
        $h1->setAction(InteractionActionEnum::APPROVE);
        $h1->setActionReason('Professional account approved by admin');
        $h1->setCreatedAt(new \DateTime('2025-11-15 10:30:00'));
        $manager->persist($h1);

        // Pressing Bernard : refus le 2026-01-22, puis approbation le 2026-01-25
        $h2 = new ProfessionalInteractionHistory();
        $h2->setAdmin($admin2);
        $h2->setProfessional($professional2);
        $h2->setAction(InteractionActionEnum::REJECT);
        $h2->setActionReason('Photos de l\'établissement manquantes. Merci de fournir au moins 3 photos de l\'intérieur.');
        $h2->setCreatedAt(new \DateTime('2026-01-22 09:15:00'));
        $manager->persist($h2);

        $h3 = new ProfessionalInteractionHistory();
        $h3->setAdmin($admin2);
        $h3->setProfessional($professional2);
        $h3->setAction(InteractionActionEnum::APPROVE);
        $h3->setActionReason('Professional account approved by admin');
        $h3->setCreatedAt(new \DateTime('2026-01-25 14:15:00'));
        $manager->persist($h3);

        // --- LaundryInteractionHistory ---

        // Lille Clean Hub (APPROVED) : approuvée directement
        $h4 = new LaundryInteractionHistory();
        $h4->setAdmin($admin2);
        $h4->setLaundry($laundry2);
        $h4->setAction(InteractionActionEnum::APPROVE);
        $h4->setActionReason('Laundry approved by admin');
        $h4->setCreatedAt(new \DateTime('2026-03-07 11:20:00'));
        $manager->persist($h4);

        // Laverie Batignolles (APPROVED) : refus puis approbation après correction
        if ($laundryBatignolles) {
            $h5 = new LaundryInteractionHistory();
            $h5->setAdmin($admin1);
            $h5->setLaundry($laundryBatignolles);
            $h5->setAction(InteractionActionEnum::REJECT);
            $h5->setActionReason('Description insuffisante et aucun équipement renseigné. Dossier à compléter.');
            $h5->setCreatedAt(new \DateTime('2026-03-08 14:00:00'));
            $manager->persist($h5);

            $h6 = new LaundryInteractionHistory();
            $h6->setAdmin($admin1);
            $h6->setLaundry($laundryBatignolles);
            $h6->setAction(InteractionActionEnum::APPROVE);
            $h6->setActionReason('Laundry approved by admin');
            $h6->setCreatedAt(new \DateTime('2026-03-12 09:30:00'));
            $manager->persist($h6);
        }

        // Latin Quarter Wash (APPROVED) : approuvée directement
        if ($laundryLatin) {
            $h7 = new LaundryInteractionHistory();
            $h7->setAdmin($admin2);
            $h7->setLaundry($laundryLatin);
            $h7->setAction(InteractionActionEnum::APPROVE);
            $h7->setActionReason('Laundry approved by admin');
            $h7->setCreatedAt(new \DateTime('2026-03-11 10:45:00'));
            $manager->persist($h7);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AdminFixtures::class,
            ProfessionalFixtures::class,
            LaundryFixtures::class,
        ];
    }
}
