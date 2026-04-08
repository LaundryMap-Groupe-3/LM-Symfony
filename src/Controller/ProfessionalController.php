<?php

namespace App\Controller;

use App\Entity\Laundry;
use App\Entity\Professional;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LaundryNoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ProfessionalController extends AbstractController
{
    #[Route('/api/professional/laundries/{id}', name: 'api_professional_laundry_show', methods: ['GET'])]
    public function getLaundry(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof User) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $professional = $entityManager->getRepository(Professional::class)->findOneBy(['user' => $user]);
        if (!$professional) {
            return $this->json(['error' => 'errors.professional_not_found'], 404);
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
        $user = $this->getUser();
        if (!$user || !$user instanceof User) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $professional = $entityManager->getRepository(Professional::class)->findOneBy(['user' => $user]);
        if (!$professional) {
            return $this->json(['error' => 'errors.professional_not_found'], 404);
        }

        $laundry = $entityManager->getRepository(Laundry::class)->find($id);
        if (!$laundry || $laundry->getProfessional()?->getId() !== $professional->getId()) {
            return $this->json(['error' => 'errors.laundry_not_found'], 404);
        }

        $payload = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('establishmentName', $payload) && is_string($payload['establishmentName'])) {
            $laundry->setEstablishmentName($payload['establishmentName']);
        }

        if (array_key_exists('description', $payload)) {
            $laundry->setDescription(is_string($payload['description']) ? $payload['description'] : null);
        }

        if (array_key_exists('contactPhone', $payload)) {
            $professional->setPhone(is_string($payload['contactPhone']) ? $payload['contactPhone'] : null);
        }

        if (isset($payload['address']) && is_array($payload['address'])) {
            $address = $laundry->getAddress();
            if (isset($payload['address']['address']) && is_string($payload['address']['address'])) {
                $address->setAddress($payload['address']['address']);
            }
            if (isset($payload['address']['street']) && is_string($payload['address']['street'])) {
                $address->setStreet($payload['address']['street']);
            }
            if (isset($payload['address']['postalCode']) && is_numeric($payload['address']['postalCode'])) {
                $address->setPostalCode((int) $payload['address']['postalCode']);
            }
            if (isset($payload['address']['city']) && is_string($payload['address']['city'])) {
                $address->setCity($payload['address']['city']);
            }
            if (isset($payload['address']['country']) && is_string($payload['address']['country'])) {
                $address->setCountry($payload['address']['country']);
            }
        }

        $laundry->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return $this->json($this->formatLaundry($laundry));
    }

    #[Route('/api/professional/laundries', name: 'api_professional_laundries', methods: ['GET'])]
    public function getLaundries(EntityManagerInterface $entityManager, LaundryNoteRepository $laundryNoteRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof User) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $professional = $entityManager->getRepository(Professional::class)->findOneBy(['user' => $user]);
        if (!$professional) {
            return $this->json(['laundries' => []]);
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

    private function formatLaundry(Laundry $laundry): array
    {
        $address = $laundry->getAddress();
        $professional = $laundry->getProfessional();

        return [
            'id' => $laundry->getId(),
            'establishmentName' => $laundry->getEstablishmentName() ?? '',
            'contactPhone' => $professional?->getPhone() ?? '',
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
            'showPreciseAddress' => false,
            'washingMachines6kg' => '',
            'washingMachines8kg' => '',
            'washingMachines10kg' => '',
            'washingMachines12kgPlus' => '',
            'dryers6kg' => '',
            'dryers8kg' => '',
            'dryers10kg' => '',
            'dryers12kgPlus' => '',
            'services' => [
                'foldingArea' => false,
                'detergentDispenser' => false,
                'cardPayment' => false,
                'cashPayment' => false,
                'parking' => false,
                'wifi' => false,
            ],
            'paymentMethods' => [
                'card' => false,
                'cash' => false,
                'contactless' => false,
                'mobile' => false,
            ],
            'customServices' => [],
            'openingHours' => [
                'monday' => ['open' => '', 'close' => ''],
                'tuesday' => ['open' => '', 'close' => ''],
                'wednesday' => ['open' => '', 'close' => ''],
                'thursday' => ['open' => '', 'close' => ''],
                'friday' => ['open' => '', 'close' => ''],
                'saturday' => ['open' => '', 'close' => ''],
                'sunday' => ['open' => '', 'close' => ''],
            ],
            'washingPrice6kg' => '',
            'washingPrice8kg' => '',
            'washingPrice10kg' => '',
            'washingPrice12kgPlus' => '',
            'dryingPrice6kg' => '',
            'dryingPrice8kg' => '',
            'dryingPrice10kg' => '',
            'dryingPrice12kgPlus' => '',
        ];
    }
}
