<?php

namespace App\Controller;

use App\Entity\Laundry;
use App\Repository\LaundryRepository;
use App\Repository\LaundryNoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class LaundryController extends AbstractController
{
    public function __construct(
        private LaundryRepository $laundryRepository,
        private NormalizerInterface $serializer
    ) {
    }

    #[Route('/api/laundries', name: 'api_laundries', methods: ['GET'])]
    public function getAllLaundries(LaundryRepository $laundryRepository, LaundryNoteRepository $laundryNoteRepository): JsonResponse
    {
        $laundries = $laundryRepository->findBy(['deletedAt' => null]);
        $result = [];
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
                'latitude' => $address?->getLatitude() ?? null,
                'longitude' => $address?->getLongitude() ?? null,
                'createdAt' => $laundry->getCreatedAt()?->format('c') ?? '',
                'updatedAt' => $laundry->getUpdatedAt()?->format('c') ?? '',
            ];
            $laundryIds[] = $laundry->getId();
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
            'all' => count($laundryIds),
            'averageNote' => $averageNote,
            'reviewCount' => $reviewCount,
        ];

        return $this->json([
            'laundries' => $result,
            'stats' => $stats
        ]);
    }

    #[Route('/api/laundry/{id}', name: 'api_laundry_content', methods: ['GET'])]
    public function getLaundry(int $id): JsonResponse
    {
        $laundry = $this->laundryRepository->find($id);
        if(!$laundry) {
            return $this->json(['message' => 'errors.laundry_not_found'], 404);
        }

        $laundryData = $this->serializer->normalize($laundry, null, ['groups' => ['laundry:read']]);

        return  $this->json(['laundry' => $laundryData], 200);

    }
}