<?php

namespace App\Controller;

use App\Entity\Laundry;
use App\Entity\Professional;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LaundryNoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ProfessionalController extends AbstractController
{
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
}
