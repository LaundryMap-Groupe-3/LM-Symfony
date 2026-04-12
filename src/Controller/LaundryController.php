<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\Laundry;
use App\Repository\LaundryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

class LaundryController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer
    ) {}
    
    #[Route('/api/admin/laundry/prending', name: 'api_admin_laundry_pending', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getPendingProfessionals(
        Request $request,
        LaundryRepository $laundryRepository
    ): JsonResponse
    {
        try {
            $user = $this->getUser();
            
            if (!$user instanceof Admin) {
                return $this->json(['error' => 'errors.unauthorized'], 403);
            }

            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
            $offset = ($page - 1) * $limit;

            $laundries = $laundryRepository->findPendingLaundries($limit, $offset);
            $total = $laundryRepository->countPendingLaundries();
            
            $data = $this->serializer->serialize($laundries, 'json', ['groups' => ['laundry:read']]);

            return JsonResponse::fromJsonString(
                json_encode([
                    'data' => json_decode($data),
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => (int) ceil($total / $limit),
                    ],
                ])
            );
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}