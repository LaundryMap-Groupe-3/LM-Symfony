<?php

namespace App\DataFixtures;

use App\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ServiceFixtures extends Fixture
{
    public const SELF_SERVICE_NAME = 'Self-service 24/7';
    public const IRONING_STATION_NAME = 'Ironing station';
    public const LAUNDRY_FOLDING_NAME = 'Laundry folding';

    public function load(ObjectManager $manager): void
    {
        $selfService = new Service();
        $selfService->setName(self::SELF_SERVICE_NAME);
        $manager->persist($selfService);

        $ironingStation = new Service();
        $ironingStation->setName(self::IRONING_STATION_NAME);
        $manager->persist($ironingStation);

        $laundryFolding = new Service();
        $laundryFolding->setName(self::LAUNDRY_FOLDING_NAME);
        $manager->persist($laundryFolding);

        $manager->flush();
    }
}
