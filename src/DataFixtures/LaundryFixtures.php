<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\Laundry;
use App\Entity\LaundryClosure;
use App\Entity\LaundryEquipment;
use App\Entity\LaundryExceptionalClosure;
use App\Entity\LaundryFavorite;
use App\Entity\LaundryNote;
use App\Entity\LaundryNoteReport;
use App\Entity\LaundryPayment;
use App\Entity\LaundryService;
use App\Entity\PaymentMethod;
use App\Entity\Professional;
use App\Entity\Service;
use App\Entity\User;
use App\Enum\DayOfWeekEnum;
use App\Enum\GeolocalizationStatusEnum;
use App\Enum\LaundryEquipmentTypeEnum;
use App\Enum\LaundryNoteReportReasonEnum;
use App\Enum\LaundryStatusEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

class LaundryFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTime('2026-04-10 10:00:00');

        $professional1 = $this->findOneOrFail(
            $manager,
            Professional::class,
            ['companyName' => 'Laverie Martin'],
            'Laverie Martin'
        );

        $professional2 = $this->findOneOrFail(
            $manager,
            Professional::class,
            ['companyName' => 'Pressing Bernard'],
            'Pressing Bernard'
        );

        $user1 = $this->findOneOrFail(
            $manager,
            User::class,
            ['email' => 'marie.dupont@example.com'],
            'marie.dupont@example.com'
        );

        $user2 = $this->findOneOrFail(
            $manager,
            User::class,
            ['email' => 'jean.martin@example.com'],
            'jean.martin@example.com'
        );

        $laundryAddress1 = $this->createAddress(
            '12 Rue du Faubourg Saint-Antoine 75012 Paris',
            '12 Rue du Faubourg Saint-Antoine',
            75012,
            'Paris',
            48.8516,
            2.3762
        );
        $manager->persist($laundryAddress1);

        $laundryAddress2 = $this->createAddress(
            '101 Boulevard Victor Hugo 59000 Lille',
            '101 Boulevard Victor Hugo',
            59000,
            'Lille',
            50.6329,
            3.0586
        );
        $manager->persist($laundryAddress2);

        $serviceSelf = $this->findOneOrFail(
            $manager,
            Service::class,
            ['name' => ServiceFixtures::SELF_SERVICE_NAME],
            ServiceFixtures::SELF_SERVICE_NAME
        );

        $serviceIron = $this->findOneOrFail(
            $manager,
            Service::class,
            ['name' => ServiceFixtures::IRONING_STATION_NAME],
            ServiceFixtures::IRONING_STATION_NAME
        );

        $serviceFolding = $this->findOneOrFail(
            $manager,
            Service::class,
            ['name' => ServiceFixtures::LAUNDRY_FOLDING_NAME],
            ServiceFixtures::LAUNDRY_FOLDING_NAME
        );

        $paymentCard = $this->findOneOrFail(
            $manager,
            PaymentMethod::class,
            ['name' => PaymentMethodFixtures::CARD_NAME],
            PaymentMethodFixtures::CARD_NAME
        );

        $paymentCash = $this->findOneOrFail(
            $manager,
            PaymentMethod::class,
            ['name' => PaymentMethodFixtures::CASH_NAME],
            PaymentMethodFixtures::CASH_NAME
        );

        $paymentContactless = $this->findOneOrFail(
            $manager,
            PaymentMethod::class,
            ['name' => PaymentMethodFixtures::CONTACTLESS_NAME],
            PaymentMethodFixtures::CONTACTLESS_NAME
        );

        $laundry1 = new Laundry();
        $laundry1->setProfessional($professional1);
        $laundry1->setStatus(LaundryStatusEnum::PENDING);
        $laundry1->setAddress($laundryAddress1);
        $laundry1->setEstablishmentName('Laverie Bastille Express');
        $laundry1->setContactEmail('contact@bastille-express.test');
        $laundry1->setDescription('Grande laverie avec des machines a laver et seche-linge modernes.');
        $laundry1->setCreatedAt(new \DateTime('2026-03-01 08:45:00'));
        $laundry1->setUpdatedAt(new \DateTime('2026-04-10 09:20:00'));
        $manager->persist($laundry1);

        $laundry2 = new Laundry();
        $laundry2->setProfessional($professional2);
        $laundry2->setStatus(LaundryStatusEnum::APPROVED);
        $laundry2->setAddress($laundryAddress2);
        $laundry2->setEstablishmentName('Lille Clean Hub');
        $laundry2->setContactEmail('hello@lille-clean-hub.test');
        $laundry2->setDescription('Laverie de quartier avec espace de pliage et cycles rapides.');
        $laundry2->setCreatedAt(new \DateTime('2026-03-05 09:15:00'));
        $laundry2->setUpdatedAt(new \DateTime('2026-04-09 17:05:00'));
        $manager->persist($laundry2);

        $approvedLaundryData = [
            [
                'professional' => $professional1,
                'address' => '28 Avenue de Clichy 75017 Paris',
                'street' => '28 Avenue de Clichy',
                'postalCode' => 75017,
                'city' => 'Paris',
                'latitude' => 48.8852,
                'longitude' => 2.3256,
                'establishmentName' => 'Laverie Batignolles 24-7',
                'contactEmail' => 'contact@batignolles24-7.test',
                'description' => 'Laverie en libre-service ouverte tard, machines recentes et espace attente.',
                'createdAt' => '2026-03-06 08:30:00',
                'updatedAt' => '2026-04-11 10:45:00',
            ],
            [
                'professional' => $professional2,
                'address' => '14 Rue Lecourbe 75015 Paris',
                'street' => '14 Rue Lecourbe',
                'postalCode' => 75015,
                'city' => 'Paris',
                'latitude' => 48.8436,
                'longitude' => 2.3043,
                'establishmentName' => 'Paris Sud Lavage Pro',
                'contactEmail' => 'hello@parissud-lavage-pro.test',
                'description' => 'Laverie de quartier avec cycles eco et zone de pliage fonctionnelle.',
                'createdAt' => '2026-03-07 10:20:00',
                'updatedAt' => '2026-04-10 16:35:00',
            ],
            [
                'professional' => $professional1,
                'address' => '9 Rue de Belleville 75020 Paris',
                'street' => '9 Rue de Belleville',
                'postalCode' => 75020,
                'city' => 'Paris',
                'latitude' => 48.8722,
                'longitude' => 2.3794,
                'establishmentName' => 'Belleville Clean Station',
                'contactEmail' => 'contact@belleville-clean-station.test',
                'description' => 'Grand espace de lavage avec seche-linge grande capacite.',
                'createdAt' => '2026-03-08 11:10:00',
                'updatedAt' => '2026-04-12 09:05:00',
            ],
            [
                'professional' => $professional2,
                'address' => '3 Rue Monge 75005 Paris',
                'street' => '3 Rue Monge',
                'postalCode' => 75005,
                'city' => 'Paris',
                'latitude' => 48.8453,
                'longitude' => 2.3516,
                'establishmentName' => 'Latin Quarter Wash',
                'contactEmail' => 'contact@latin-quarter-wash.test',
                'description' => 'Laverie rapide proche des universites, paiement sans contact accepte.',
                'createdAt' => '2026-03-09 09:50:00',
                'updatedAt' => '2026-04-11 18:40:00',
            ],
            [
                'professional' => $professional1,
                'address' => '41 Rue Ordener 75018 Paris',
                'street' => '41 Rue Ordener',
                'postalCode' => 75018,
                'city' => 'Paris',
                'latitude' => 48.8921,
                'longitude' => 2.3477,
                'establishmentName' => 'Montmartre Lavage Express',
                'contactEmail' => 'hello@montmartre-lavage-express.test',
                'description' => 'Machines performantes et sechoirs ventiles pour linge volumineux.',
                'createdAt' => '2026-03-10 08:05:00',
                'updatedAt' => '2026-04-13 12:20:00',
            ],
            [
                'professional' => $professional2,
                'address' => '11 Avenue Jean Legendre 60200 Compiegne',
                'street' => '11 Avenue Jean Legendre',
                'postalCode' => 60200,
                'city' => 'Compiegne',
                'latitude' => 49.4179,
                'longitude' => 2.8261,
                'establishmentName' => 'Compiegne Wash Center',
                'contactEmail' => 'contact@compiegne-wash-center.test',
                'description' => 'Laverie moderne avec programmes courts et espace repassage.',
                'createdAt' => '2026-03-11 10:00:00',
                'updatedAt' => '2026-04-14 15:10:00',
            ],
            [
                'professional' => $professional1,
                'address' => '5 Rue de Paris 60000 Beauvais',
                'street' => '5 Rue de Paris',
                'postalCode' => 60000,
                'city' => 'Beauvais',
                'latitude' => 49.4297,
                'longitude' => 2.0821,
                'establishmentName' => 'Beauvais Lavage Plus',
                'contactEmail' => 'contact@beauvais-lavage-plus.test',
                'description' => 'Laverie en centre-ville avec suivi de cycles sur ecran.',
                'createdAt' => '2026-03-12 11:45:00',
                'updatedAt' => '2026-04-15 14:00:00',
            ],
            [
                'professional' => $professional2,
                'address' => '22 Rue de la Republique 60100 Creil',
                'street' => '22 Rue de la Republique',
                'postalCode' => 60100,
                'city' => 'Creil',
                'latitude' => 49.2561,
                'longitude' => 2.4827,
                'establishmentName' => 'Creil Laundry Hub',
                'contactEmail' => 'hello@creil-laundry-hub.test',
                'description' => 'Etablissement pratique avec machines 8kg a 18kg.',
                'createdAt' => '2026-03-13 09:35:00',
                'updatedAt' => '2026-04-15 17:25:00',
            ],
            [
                'professional' => $professional1,
                'address' => '17 Rue du Chatel 60300 Senlis',
                'street' => '17 Rue du Chatel',
                'postalCode' => 60300,
                'city' => 'Senlis',
                'latitude' => 49.2064,
                'longitude' => 2.5869,
                'establishmentName' => 'Senlis Lavage Minute',
                'contactEmail' => 'contact@senlis-lavage-minute.test',
                'description' => 'Laverie locale avec bornes de paiement carte et espece.',
                'createdAt' => '2026-03-14 08:55:00',
                'updatedAt' => '2026-04-16 13:40:00',
            ],
            [
                'professional' => $professional2,
                'address' => '8 Avenue du Marechal Joffre 60500 Chantilly',
                'street' => '8 Avenue du Marechal Joffre',
                'postalCode' => 60500,
                'city' => 'Chantilly',
                'latitude' => 49.1945,
                'longitude' => 2.4682,
                'establishmentName' => 'Chantilly Press and Wash',
                'contactEmail' => 'contact@chantilly-press-and-wash.test',
                'description' => 'Laverie propre et calme avec sechoirs economes en energie.',
                'createdAt' => '2026-03-15 10:40:00',
                'updatedAt' => '2026-04-16 19:05:00',
            ],
        ];

        $approvedLaundries = [];

        foreach ($approvedLaundryData as $approvedLaundry) {
            $approvedAddress = $this->createAddress(
                $approvedLaundry['address'],
                $approvedLaundry['street'],
                $approvedLaundry['postalCode'],
                $approvedLaundry['city'],
                $approvedLaundry['latitude'],
                $approvedLaundry['longitude']
            );
            $manager->persist($approvedAddress);

            $laundry = new Laundry();
            $laundry->setProfessional($approvedLaundry['professional']);
            $laundry->setStatus(LaundryStatusEnum::APPROVED);
            $laundry->setAddress($approvedAddress);
            $laundry->setEstablishmentName($approvedLaundry['establishmentName']);
            $laundry->setContactEmail($approvedLaundry['contactEmail']);
            $laundry->setDescription($approvedLaundry['description']);
            $laundry->setCreatedAt(new \DateTime($approvedLaundry['createdAt']));
            $laundry->setUpdatedAt(new \DateTime($approvedLaundry['updatedAt']));

            $manager->persist($laundry);
            $approvedLaundries[] = $laundry;
        }

        $closure1 = new LaundryClosure();
        $closure1->setLaundry($laundry1);
        $closure1->setDay(DayOfWeekEnum::SUNDAY);
        $closure1->setStartTime(new \DateTime('08:00:00'));
        $closure1->setEndTime(new \DateTime('12:00:00'));
        $closure1->setCreatedAt($now);
        $closure1->setUpdatedAt($now);
        $manager->persist($closure1);

        $closure2 = new LaundryClosure();
        $closure2->setLaundry($laundry2);
        $closure2->setDay(DayOfWeekEnum::MONDAY);
        $closure2->setStartTime(new \DateTime('07:00:00'));
        $closure2->setEndTime(new \DateTime('09:00:00'));
        $closure2->setCreatedAt($now);
        $closure2->setUpdatedAt($now);
        $manager->persist($closure2);

        $exceptionalClosure = new LaundryExceptionalClosure();
        $exceptionalClosure->setLaundry($laundry1);
        $exceptionalClosure->setStartDate(new \DateTime('2026-08-15 00:00:00'));
        $exceptionalClosure->setEndDate(new \DateTime('2026-08-16 23:59:59'));
        $exceptionalClosure->setReason('Maintenance work');
        $exceptionalClosure->setCreatedAt($now);
        $manager->persist($exceptionalClosure);

        $equipment1 = new LaundryEquipment();
        $equipment1->setLaundry($laundry1);
        $equipment1->setEquipmentReference(2001);
        $equipment1->setName('Washer XL 14kg');
        $equipment1->setType(LaundryEquipmentTypeEnum::WASHING_MACHINE);
        $equipment1->setCapacity(14);
        $equipment1->setPrice(7.50);
        $equipment1->setDuration(38);
        $manager->persist($equipment1);

        $equipment2 = new LaundryEquipment();
        $equipment2->setLaundry($laundry1);
        $equipment2->setEquipmentReference(2002);
        $equipment2->setName('Dryer Turbo 18kg');
        $equipment2->setType(LaundryEquipmentTypeEnum::DRYER);
        $equipment2->setCapacity(18);
        $equipment2->setPrice(4.20);
        $equipment2->setDuration(20);
        $manager->persist($equipment2);

        $equipment3 = new LaundryEquipment();
        $equipment3->setLaundry($laundry2);
        $equipment3->setEquipmentReference(3001);
        $equipment3->setName('Washer Eco 8kg');
        $equipment3->setType(LaundryEquipmentTypeEnum::WASHING_MACHINE);
        $equipment3->setCapacity(8);
        $equipment3->setPrice(4.90);
        $equipment3->setDuration(35);
        $manager->persist($equipment3);

        $laundryService1 = new LaundryService();
        $laundryService1->setLaundry($laundry1);
        $laundryService1->setService($serviceSelf);
        $manager->persist($laundryService1);

        $laundryService2 = new LaundryService();
        $laundryService2->setLaundry($laundry1);
        $laundryService2->setService($serviceIron);
        $manager->persist($laundryService2);

        $laundryService3 = new LaundryService();
        $laundryService3->setLaundry($laundry2);
        $laundryService3->setService($serviceSelf);
        $manager->persist($laundryService3);

        $laundryService4 = new LaundryService();
        $laundryService4->setLaundry($laundry2);
        $laundryService4->setService($serviceFolding);
        $manager->persist($laundryService4);

        $laundryPayment1 = new LaundryPayment();
        $laundryPayment1->setLaundry($laundry1);
        $laundryPayment1->setPaymentMethod($paymentCard);
        $manager->persist($laundryPayment1);

        $laundryPayment2 = new LaundryPayment();
        $laundryPayment2->setLaundry($laundry1);
        $laundryPayment2->setPaymentMethod($paymentContactless);
        $manager->persist($laundryPayment2);

        $laundryPayment3 = new LaundryPayment();
        $laundryPayment3->setLaundry($laundry2);
        $laundryPayment3->setPaymentMethod($paymentCard);
        $manager->persist($laundryPayment3);

        $laundryPayment4 = new LaundryPayment();
        $laundryPayment4->setLaundry($laundry2);
        $laundryPayment4->setPaymentMethod($paymentCash);
        $manager->persist($laundryPayment4);

        foreach ($approvedLaundries as $index => $approvedLaundry) {
            $laundryService = new LaundryService();
            $laundryService->setLaundry($approvedLaundry);
            $laundryService->setService($index % 2 === 0 ? $serviceSelf : $serviceFolding);
            $manager->persist($laundryService);

            $laundryPayment = new LaundryPayment();
            $laundryPayment->setLaundry($approvedLaundry);
            $laundryPayment->setPaymentMethod($index % 3 === 0 ? $paymentContactless : $paymentCard);
            $manager->persist($laundryPayment);
        }

        $favorite1 = new LaundryFavorite();
        $favorite1->setLaundry($laundry1);
        $favorite1->setUser($user1);
        $manager->persist($favorite1);

        $favorite2 = new LaundryFavorite();
        $favorite2->setLaundry($laundry2);
        $favorite2->setUser($user1);
        $manager->persist($favorite2);

        $favorite3 = new LaundryFavorite();
        $favorite3->setLaundry($laundry2);
        $favorite3->setUser($user2);
        $manager->persist($favorite3);

        $note1 = new LaundryNote();
        $note1->setLaundry($laundry1);
        $note1->setUser($user1);
        $note1->setRating(5);
        $note1->setRatedAt(new \DateTime('2026-04-01 20:15:00'));
        $note1->setComment('Very clean and machines are fast.');
        $note1->setCommentedAt(new \DateTime('2026-04-01 20:17:00'));
        $note1->setResponse('Thank you for your feedback.');
        $note1->setRespondedAt(new \DateTime('2026-04-02 09:00:00'));
        $manager->persist($note1);

        $note2 = new LaundryNote();
        $note2->setLaundry($laundry2);
        $note2->setUser($user2);
        $note2->setRating(3);
        $note2->setRatedAt(new \DateTime('2026-04-03 19:05:00'));
        $note2->setComment('One dryer was out of order.');
        $note2->setCommentedAt(new \DateTime('2026-04-03 19:08:00'));
        $manager->persist($note2);

        $noteReport = new LaundryNoteReport();
        $noteReport->setLaundryNote($note2);
        $noteReport->setUser($user1);
        $noteReport->setCreatedAt(new \DateTime('2026-04-04 10:00:00'));
        $noteReport->setReason(LaundryNoteReportReasonEnum::EQUIPMENT_BROKEN);
        $noteReport->setComment('Issue confirmed during my visit.');
        $manager->persist($noteReport);

        $manager->flush();
    }

    private function createAddress(
        string $address,
        string $street,
        int $postalCode,
        string $city,
        float $latitude,
        float $longitude
    ): Address {
        $addressEntity = new Address();
        $addressEntity->setAddress($address);
        $addressEntity->setStreet($street);
        $addressEntity->setPostalCode($postalCode);
        $addressEntity->setCity($city);
        $addressEntity->setCountry('France');
        $addressEntity->setLatitude($latitude);
        $addressEntity->setLongitude($longitude);
        $addressEntity->setGeolocalizationStatus(GeolocalizationStatusEnum::VERIFIED);

        return $addressEntity;
    }

    private function findOneOrFail(
        ObjectManager $manager,
        string $entityClass,
        array $criteria,
        string $label
    ): object {
        $entity = $manager->getRepository($entityClass)->findOneBy($criteria);

        if ($entity === null) {
            throw new RuntimeException(sprintf('%s not found for LaundryFixtures.', $label));
        }

        return $entity;
    }

    public function getDependencies(): array
    {
        return [
            ServiceFixtures::class,
            PaymentMethodFixtures::class,
            UserFixtures::class,
            ProfessionalFixtures::class,
        ];
    }
}
