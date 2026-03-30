<?php

namespace App\Controller;

use App\Entity\Laundry;
use App\Entity\Professional;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ProfessionalController extends AbstractController
{
    #[Route('/api/professional/laundries', name: 'api_professional_laundries', methods: ['GET'])]
    public function getLaundries(EntityManagerInterface $entityManager): JsonResponse
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
        foreach ($laundries as $laundry) {
            $address = $laundry->getAddress();
            $result[] = [
                'id' => $laundry->getId(),
                'establishmentName' => $laundry->getEstablishmentName() ?? '',
                'status' => $laundry->getStatus()?->value ?? '',
                'address' => $address?->getAddress() ?? '',
                'postalCode' => $address?->getPostalCode() ?? '',
                'city' => $address?->getCity() ?? '',
                'createdAt' => $laundry->getCreatedAt()?->format('c') ?? '',
                'updatedAt' => $laundry->getUpdatedAt()?->format('c') ?? '',
            ];
        }

        return $this->json(['laundries' => $result]);
    }
}
