<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Laundry;
use App\Entity\LaundryClosure;
use App\Entity\LaundryEquipment;
use App\Entity\LaundryPayment;
use App\Entity\LaundryService;
use App\Entity\PaymentMethod;
use App\Entity\Professional;
use App\Entity\Service;
use App\Entity\User;
use App\Enum\DayOfWeekEnum;
use App\Enum\GeolocalizationStatusEnum;
use App\Enum\LaundryEquipmentTypeEnum;
use App\Enum\LaundryStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LaundryNoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ProfessionalController extends AbstractController
{
    #[Route('/api/professional/laundry-options', name: 'api_professional_laundry_options', methods: ['GET'])]
    public function getLaundryOptions(EntityManagerInterface $entityManager): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $services = $entityManager->getRepository(Service::class)->findBy([], ['name' => 'ASC']);
        $paymentMethods = $entityManager->getRepository(PaymentMethod::class)->findBy([], ['name' => 'ASC']);

        return $this->json([
            'services' => array_map(
                static fn (Service $service): array => [
                    'id' => $service->getId(),
                    'name' => $service->getName(),
                ],
                $services
            ),
            'paymentMethods' => array_map(
                static fn (PaymentMethod $paymentMethod): array => [
                    'id' => $paymentMethod->getId(),
                    'name' => $paymentMethod->getName(),
                ],
                $paymentMethods
            ),
        ]);
    }

    #[Route('/api/professional/laundries', name: 'api_professional_laundry_create', methods: ['POST'])]
    public function createLaundry(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'errors.invalid_payload'], 400);
        }

        $errors = $this->validateLaundryPayload($payload, true);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        $laundry = new Laundry();
        $laundry->setProfessional($professional);
        $laundry->setStatus(LaundryStatusEnum::PENDING);
        $laundry->setCreatedAt(new \DateTime());

        $address = new Address();
        $address->setAddress('');
        $address->setStreet('');
        $address->setPostalCode(0);
        $address->setCity('');
        $address->setCountry('');
        $address->setGeolocalizationStatus(GeolocalizationStatusEnum::PENDING);
        $laundry->setAddress($address);

        $this->applyLaundryPayload($payload, $laundry, $professional, $entityManager);

        $entityManager->persist($address);
        $entityManager->persist($laundry);
        $entityManager->flush();

        return $this->json($this->formatLaundry($laundry), 201);
    }

    #[Route('/api/professional/laundries/{id}', name: 'api_professional_laundry_show', methods: ['GET'])]
    public function getLaundry(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $laundry = $entityManager->getRepository(Laundry::class)->find($id);
        if (!$laundry || $laundry->getProfessional()?->getId() !== $professional->getId()) {
            return $this->json(['error' => 'errors.laundry_not_found'], 404);
        }

        return $this->json($this->formatLaundry($laundry));
    }

    #[Route('/api/professional/laundries/{id}', name: 'api_professional_laundry_update', methods: ['PUT'])]
    public function updateLaundry(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $laundry = $entityManager->getRepository(Laundry::class)->find($id);
        if (!$laundry || $laundry->getProfessional()?->getId() !== $professional->getId()) {
            return $this->json(['error' => 'errors.laundry_not_found'], 404);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'errors.invalid_payload'], 400);
        }

        $errors = $this->validateLaundryPayload($payload, false);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        $this->applyLaundryPayload($payload, $laundry, $professional, $entityManager);
        $entityManager->flush();

        return $this->json($this->formatLaundry($laundry));
    }

    #[Route('/api/professional/laundries', name: 'api_professional_laundries', methods: ['GET'])]
    public function getLaundries(EntityManagerInterface $entityManager, LaundryNoteRepository $laundryNoteRepository): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $laundries = $professional->getLaundries();
        $result = [];
        $total = 0;
        $approved = 0;
        $pending = 0;
        $rejected = 0;
        $laundryIds = [];
        foreach ($laundries as $laundry) {
            $address = $laundry->getAddress();
            $status = $laundry->getStatus()?->value ?? '';
            $result[] = [
                'id' => $laundry->getId(),
                'establishmentName' => $laundry->getEstablishmentName() ?? '',
                'status' => $status,
                'address' => $address?->getAddress() ?? '',
                'postalCode' => $address?->getPostalCode() ?? '',
                'city' => $address?->getCity() ?? '',
                'createdAt' => $laundry->getCreatedAt()?->format('c') ?? '',
                'updatedAt' => $laundry->getUpdatedAt()?->format('c') ?? '',
            ];
            $total++;
            $laundryIds[] = $laundry->getId();
            if ($status === 'approved') {
                $approved++;
            } elseif ($status === 'pending') {
                $pending++;
            } elseif ($status === 'rejected') {
                $rejected++;
            }
        }

        // Calcul via le repository
        $averageNote = null;
        $reviewCount = 0;
        if (count($laundryIds) > 0) {
            $stats = $laundryNoteRepository->getAverageRatingAndCountByLaundryIds($laundryIds);
            if ($stats && $stats['avg_rating'] !== null) {
                $averageNote = round((float)$stats['avg_rating'], 2);
                $reviewCount = (int)$stats['review_count'];
            }
        }

        $stats = [
            'total' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
            'all' => $total,
            'averageNote' => $averageNote,
            'reviewCount' => $reviewCount,
        ];

        return $this->json([
            'laundries' => $result,
            'stats' => $stats
        ]);
    }

    private function getCurrentProfessional(EntityManagerInterface $entityManager): ?Professional
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return null;
        }

        return $entityManager->getRepository(Professional::class)->findOneBy(['user' => $user]);
    }

    private function validateLaundryPayload(array $payload, bool $isCreate): array
    {
        $errors = [];

        $establishmentName = trim((string) ($payload['establishmentName'] ?? ''));
        if ($isCreate && $establishmentName == '') {
            $errors['establishmentName'] = 'validation.company_name_required';
        } elseif ($establishmentName !== '' && mb_strlen($establishmentName) > 50) {
            $errors['establishmentName'] = 'validation.company_name_max_length';
        }

        if (isset($payload['contactPhone']) && $payload['contactPhone'] !== null) {
            $phone = trim((string) $payload['contactPhone']);
            if ($phone !== '' && !preg_match('/^[0-9+\s().-]{10,20}$/', $phone)) {
                $errors['contactPhone'] = 'validation.phone_invalid';
            }
        }

        $address = $payload['address'] ?? [];
        if ($isCreate && !is_array($address)) {
            $errors['address'] = 'validation.address_required';
            return $errors;
        }

        if (is_array($address)) {
            $street = trim((string) ($address['street'] ?? ''));
            $city = trim((string) ($address['city'] ?? ''));
            $country = trim((string) ($address['country'] ?? ''));
            $postalCodeRaw = (string) ($address['postalCode'] ?? '');

            if ($isCreate && $street === '') {
                $errors['street'] = 'validation.street_required';
            }
            if ($isCreate && $city === '') {
                $errors['city'] = 'validation.city_required';
            }
            if ($isCreate && $country === '') {
                $errors['country'] = 'validation.country_required';
            }
            if ($isCreate && $postalCodeRaw === '') {
                $errors['postalCode'] = 'validation.postal_code_required';
            }

            if ($postalCodeRaw !== '' && !preg_match('/^\d{5}$/', $postalCodeRaw)) {
                $errors['postalCode'] = 'validation.postal_code_invalid';
            }
        }

        return $errors;
    }

    private function applyLaundryPayload(
        array $payload,
        Laundry $laundry,
        Professional $professional,
        EntityManagerInterface $entityManager
    ): void {
        if (array_key_exists('establishmentName', $payload)) {
            $laundry->setEstablishmentName(trim((string) $payload['establishmentName']));
        }

        if (array_key_exists('description', $payload)) {
            $description = trim((string) ($payload['description'] ?? ''));
            $laundry->setDescription($description !== '' ? $description : null);
        }

        if (array_key_exists('contactPhone', $payload)) {
            $contactPhone = trim((string) ($payload['contactPhone'] ?? ''));
            $professional->setPhone($contactPhone !== '' ? $contactPhone : null);
        }

        if (array_key_exists('showPreciseAddress', $payload)) {
            $showPreciseAddress = filter_var($payload['showPreciseAddress'], FILTER_VALIDATE_BOOL);
            if (!$showPreciseAddress) {
                $laundry->setWiLineReference(null);
            } elseif ($this->toIntOrNull($payload['wiLineReference'] ?? null) !== null) {
                $laundry->setWiLineReference($this->toIntOrNull($payload['wiLineReference']));
            }
        }

        if (isset($payload['address']) && is_array($payload['address'])) {
            $address = $laundry->getAddress();
            $street = trim((string) ($payload['address']['street'] ?? $address->getStreet()));
            $city = trim((string) ($payload['address']['city'] ?? $address->getCity()));
            $country = trim((string) ($payload['address']['country'] ?? $address->getCountry()));
            $postalCode = $this->toIntOrNull($payload['address']['postalCode'] ?? null);

            if ($street !== '') {
                $address->setStreet($street);
            }
            if ($city !== '') {
                $address->setCity($city);
            }
            if ($country !== '') {
                $address->setCountry($country);
            }
            if ($postalCode !== null) {
                $address->setPostalCode($postalCode);
            }

            $addressLine = trim(sprintf('%s %s %s', $address->getStreet(), (string) $address->getPostalCode(), $address->getCity()));
            $address->setAddress($addressLine);

            if (!isset($payload['address']['latitude'])) {
                $address->setLatitude(null);
            }
            if (!isset($payload['address']['longitude'])) {
                $address->setLongitude(null);
            }
            $address->setGeolocalizationStatus(GeolocalizationStatusEnum::PENDING);
        }

        $this->syncLaundryServices($laundry, $payload['serviceIds'] ?? null, $entityManager);
        $this->syncLaundryPayments($laundry, $payload['paymentMethodIds'] ?? null, $entityManager);
        $this->syncLaundryClosures($laundry, $payload, $entityManager);
        $this->syncLaundryEquipments($laundry, $payload, $entityManager);

        $laundry->setUpdatedAt(new \DateTime());
    }

    private function syncLaundryServices(Laundry $laundry, mixed $serviceIds, EntityManagerInterface $entityManager): void
    {
        if (!is_array($serviceIds)) {
            return;
        }

        $targetIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds), static fn (int $id): bool => $id > 0)));
        $targetIdMap = array_fill_keys($targetIds, true);
        $existingById = [];

        foreach ($laundry->getLaundryServices()->toArray() as $laundryService) {
            $service = $laundryService->getService();
            $serviceId = $service?->getId();
            if ($serviceId === null) {
                continue;
            }

            $existingById[$serviceId] = $laundryService;
            if (!isset($targetIdMap[$serviceId])) {
                $laundry->removeLaundryService($laundryService);
                $entityManager->remove($laundryService);
                unset($existingById[$serviceId]);
            }
        }

        if ($targetIds === []) {
            return;
        }

        $missingIds = array_values(array_filter($targetIds, static fn (int $id): bool => !isset($existingById[$id])));
        if ($missingIds === []) {
            return;
        }

        $services = $entityManager->getRepository(Service::class)->findBy(['id' => $missingIds]);
        foreach ($services as $service) {
            $laundryService = new LaundryService();
            $laundryService->setLaundry($laundry);
            $laundryService->setService($service);
            $entityManager->persist($laundryService);
        }
    }

    private function syncLaundryPayments(Laundry $laundry, mixed $paymentMethodIds, EntityManagerInterface $entityManager): void
    {
        if (!is_array($paymentMethodIds)) {
            return;
        }

        $targetIds = array_values(array_unique(array_filter(array_map('intval', $paymentMethodIds), static fn (int $id): bool => $id > 0)));
        $targetIdMap = array_fill_keys($targetIds, true);
        $existingById = [];

        foreach ($laundry->getLaundryPayments()->toArray() as $laundryPayment) {
            $paymentMethod = $laundryPayment->getPaymentMethod();
            $paymentMethodId = $paymentMethod?->getId();
            if ($paymentMethodId === null) {
                continue;
            }

            $existingById[$paymentMethodId] = $laundryPayment;
            if (!isset($targetIdMap[$paymentMethodId])) {
                $laundry->removeLaundryPayment($laundryPayment);
                $entityManager->remove($laundryPayment);
                unset($existingById[$paymentMethodId]);
            }
        }

        if ($targetIds === []) {
            return;
        }

        $missingIds = array_values(array_filter($targetIds, static fn (int $id): bool => !isset($existingById[$id])));
        if ($missingIds === []) {
            return;
        }

        $paymentMethods = $entityManager->getRepository(PaymentMethod::class)->findBy(['id' => $missingIds]);
        foreach ($paymentMethods as $paymentMethod) {
            $laundryPayment = new LaundryPayment();
            $laundryPayment->setLaundry($laundry);
            $laundryPayment->setPaymentMethod($paymentMethod);
            $entityManager->persist($laundryPayment);
        }
    }

    private function syncLaundryClosures(Laundry $laundry, array $payload, EntityManagerInterface $entityManager): void
    {
        if (!isset($payload['openingHours']) || !is_array($payload['openingHours'])) {
            return;
        }

        foreach ($laundry->getLaundryClosures()->toArray() as $closure) {
            $laundry->removeLaundryClosure($closure);
            $entityManager->remove($closure);
        }

        $extraHours = isset($payload['openingHoursExtra']) && is_array($payload['openingHoursExtra'])
            ? $payload['openingHoursExtra']
            : [];
        $now = new \DateTime();

        foreach (DayOfWeekEnum::cases() as $dayEnum) {
            $dayKey = $dayEnum->value;
            $slots = [];

            $primary = $payload['openingHours'][$dayKey] ?? null;
            if (is_array($primary) && $this->hasOpeningSlot($primary)) {
                $slots[] = $primary;
            }

            $extras = $extraHours[$dayKey] ?? [];
            if (is_array($extras)) {
                foreach ($extras as $extraSlot) {
                    if (is_array($extraSlot) && $this->hasOpeningSlot($extraSlot)) {
                        $slots[] = $extraSlot;
                    }
                }
            }

            foreach ($slots as $slot) {
                $closure = new LaundryClosure();
                $closure->setLaundry($laundry);
                $closure->setDay($dayEnum);
                $closure->setCreatedAt($now);
                $closure->setUpdatedAt($now);
                $closure->setStartTime(\DateTime::createFromFormat('H:i', (string) $slot['open']) ?: new \DateTime('00:00'));
                $closure->setEndTime(\DateTime::createFromFormat('H:i', (string) $slot['close']) ?: new \DateTime('00:00'));
                $entityManager->persist($closure);
            }
        }
    }

    private function hasOpeningSlot(array $slot): bool
    {
        $open = trim((string) ($slot['open'] ?? ''));
        $close = trim((string) ($slot['close'] ?? ''));
        return $open !== '' && $close !== '';
    }

    private function syncLaundryEquipments(Laundry $laundry, array $payload, EntityManagerInterface $entityManager): void
    {
        $hasMachinePayload = array_key_exists('washingMachines6kg', $payload)
            || array_key_exists('washingMachines8kg', $payload)
            || array_key_exists('washingMachines10kg', $payload)
            || array_key_exists('washingMachines12kgPlus', $payload)
            || array_key_exists('dryers6kg', $payload)
            || array_key_exists('dryers8kg', $payload)
            || array_key_exists('dryers10kg', $payload)
            || array_key_exists('dryers12kgPlus', $payload);

        if (!$hasMachinePayload) {
            return;
        }

        foreach ($laundry->getLaundryEquipments()->toArray() as $equipment) {
            $entityManager->remove($equipment);
        }

        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::WASHING_MACHINE, 6, $payload, 'washingMachines6kg', 'washingPrice6kg', 35, $entityManager);
        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::WASHING_MACHINE, 8, $payload, 'washingMachines8kg', 'washingPrice8kg', 35, $entityManager);
        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::WASHING_MACHINE, 10, $payload, 'washingMachines10kg', 'washingPrice10kg', 35, $entityManager);
        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::WASHING_MACHINE, 12, $payload, 'washingMachines12kgPlus', 'washingPrice12kgPlus', 35, $entityManager);

        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::DRYER, 6, $payload, 'dryers6kg', 'dryingPrice6kg', 20, $entityManager);
        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::DRYER, 8, $payload, 'dryers8kg', 'dryingPrice8kg', 20, $entityManager);
        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::DRYER, 10, $payload, 'dryers10kg', 'dryingPrice10kg', 20, $entityManager);
        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::DRYER, 12, $payload, 'dryers12kgPlus', 'dryingPrice12kgPlus', 20, $entityManager);
    }

    private function createEquipmentsForCapacity(
        Laundry $laundry,
        LaundryEquipmentTypeEnum $type,
        int $capacity,
        array $payload,
        string $countField,
        string $priceField,
        int $duration,
        EntityManagerInterface $entityManager
    ): void {
        $count = max(0, (int) ($payload[$countField] ?? 0));
        $price = max(0.0, (float) ($payload[$priceField] ?? 0));

        for ($i = 1; $i <= $count; $i++) {
            $equipment = new LaundryEquipment();
            $equipment->setLaundry($laundry);
            $equipment->setType($type);
            $equipment->setCapacity($capacity);
            $equipment->setPrice($price);
            $equipment->setDuration($duration);
            $equipment->setName(sprintf('%s %dkg #%d', $type === LaundryEquipmentTypeEnum::WASHING_MACHINE ? 'Washer' : 'Dryer', $capacity, $i));
            $entityManager->persist($equipment);
        }
    }

    private function toIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function formatLaundry(Laundry $laundry): array
    {
        $address = $laundry->getAddress();
        $professional = $laundry->getProfessional();
        $closures = $laundry->getLaundryClosures()->toArray();
        usort(
            $closures,
            static fn (LaundryClosure $a, LaundryClosure $b): int => strcmp(
                $a->getDay()->value . $a->getStartTime()->format('H:i'),
                $b->getDay()->value . $b->getStartTime()->format('H:i')
            )
        );

        $openingHours = [
            'monday' => ['open' => '', 'close' => ''],
            'tuesday' => ['open' => '', 'close' => ''],
            'wednesday' => ['open' => '', 'close' => ''],
            'thursday' => ['open' => '', 'close' => ''],
            'friday' => ['open' => '', 'close' => ''],
            'saturday' => ['open' => '', 'close' => ''],
            'sunday' => ['open' => '', 'close' => ''],
        ];
        $openingHoursExtra = [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => [],
        ];

        foreach ($closures as $closure) {
            $day = $closure->getDay()->value;
            $slot = [
                'open' => $closure->getStartTime()->format('H:i'),
                'close' => $closure->getEndTime()->format('H:i'),
            ];

            if ($openingHours[$day]['open'] === '' && $openingHours[$day]['close'] === '') {
                $openingHours[$day] = $slot;
            } else {
                $openingHoursExtra[$day][] = $slot;
            }
        }

        $machineCounts = [
            'washingMachines6kg' => 0,
            'washingMachines8kg' => 0,
            'washingMachines10kg' => 0,
            'washingMachines12kgPlus' => 0,
            'dryers6kg' => 0,
            'dryers8kg' => 0,
            'dryers10kg' => 0,
            'dryers12kgPlus' => 0,
        ];

        $prices = [
            'washingPrice6kg' => null,
            'washingPrice8kg' => null,
            'washingPrice10kg' => null,
            'washingPrice12kgPlus' => null,
            'dryingPrice6kg' => null,
            'dryingPrice8kg' => null,
            'dryingPrice10kg' => null,
            'dryingPrice12kgPlus' => null,
        ];

        foreach ($laundry->getLaundryEquipments() as $equipment) {
            $capacity = $equipment->getCapacity();
            $capacityKey = $capacity >= 12 ? '12kgPlus' : sprintf('%dkg', $capacity);

            if ($equipment->getType() === LaundryEquipmentTypeEnum::WASHING_MACHINE) {
                $countField = sprintf('washingMachines%s', $capacityKey);
                $priceField = sprintf('washingPrice%s', $capacityKey);
            } elseif ($equipment->getType() === LaundryEquipmentTypeEnum::DRYER) {
                $countField = sprintf('dryers%s', $capacityKey);
                $priceField = sprintf('dryingPrice%s', $capacityKey);
            } else {
                continue;
            }

            if (!array_key_exists($countField, $machineCounts)) {
                continue;
            }

            $machineCounts[$countField]++;
            if ($prices[$priceField] === null) {
                $prices[$priceField] = $equipment->getPrice();
            }
        }

        $serviceIds = [];
        foreach ($laundry->getLaundryServices() as $laundryService) {
            $service = $laundryService->getService();
            if ($service !== null) {
                $serviceIds[] = $service->getId();
            }
        }

        $paymentMethodIds = [];
        foreach ($laundry->getLaundryPayments() as $laundryPayment) {
            $paymentMethod = $laundryPayment->getPaymentMethod();
            if ($paymentMethod !== null) {
                $paymentMethodIds[] = $paymentMethod->getId();
            }
        }

        return [
            'id' => $laundry->getId(),
            'establishmentName' => $laundry->getEstablishmentName() ?? '',
            'contactPhone' => $professional?->getPhone() ?? '',
            'contactEmail' => $laundry->getContactEmail() ?? '',
            'description' => $laundry->getDescription() ?? '',
            'status' => $laundry->getStatus()?->value ?? '',
            'address' => [
                'address' => $address?->getAddress() ?? '',
                'street' => $address?->getStreet() ?? '',
                'postalCode' => $address?->getPostalCode() ?? '',
                'city' => $address?->getCity() ?? '',
                'country' => $address?->getCountry() ?? '',
            ],
            'createdAt' => $laundry->getCreatedAt()?->format('c') ?? '',
            'updatedAt' => $laundry->getUpdatedAt()?->format('c') ?? '',
            'showPreciseAddress' => $laundry->getWiLineReference() !== null,
            'wiLineReference' => $laundry->getWiLineReference(),
            'serviceIds' => array_values(array_unique($serviceIds)),
            'paymentMethodIds' => array_values(array_unique($paymentMethodIds)),
            'openingHours' => $openingHours,
            'openingHoursExtra' => $openingHoursExtra,
            'washingMachines6kg' => $machineCounts['washingMachines6kg'],
            'washingMachines8kg' => $machineCounts['washingMachines8kg'],
            'washingMachines10kg' => $machineCounts['washingMachines10kg'],
            'washingMachines12kgPlus' => $machineCounts['washingMachines12kgPlus'],
            'dryers6kg' => $machineCounts['dryers6kg'],
            'dryers8kg' => $machineCounts['dryers8kg'],
            'dryers10kg' => $machineCounts['dryers10kg'],
            'dryers12kgPlus' => $machineCounts['dryers12kgPlus'],
            'washingPrice6kg' => $prices['washingPrice6kg'] ?? '',
            'washingPrice8kg' => $prices['washingPrice8kg'] ?? '',
            'washingPrice10kg' => $prices['washingPrice10kg'] ?? '',
            'washingPrice12kgPlus' => $prices['washingPrice12kgPlus'] ?? '',
            'dryingPrice6kg' => $prices['dryingPrice6kg'] ?? '',
            'dryingPrice8kg' => $prices['dryingPrice8kg'] ?? '',
            'dryingPrice10kg' => $prices['dryingPrice10kg'] ?? '',
            'dryingPrice12kgPlus' => $prices['dryingPrice12kgPlus'] ?? '',
        ];
    }
}
