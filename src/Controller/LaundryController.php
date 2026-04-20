<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\Laundry;
use App\Entity\LaundryFavorite;
use App\Entity\User;
use App\Repository\LaundryFavoriteRepository;
use App\Repository\LaundryRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

class LaundryController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private LaundryRepository $laundryRepository,
        private EntityManager $entityManager,
        private LaundryFavoriteRepository $favoriteLaundryRepository,
    ) {}
    
    #[Route('/api/admin/laundry/prending', name: 'api_admin_laundry_pending', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getPendingProfessionals(
        Request $request,
        LaundryRepository $laundryRepository,
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

    #[Route('api/laundry/{id}/favorite/add', name: 'laundry_favorite_add', methods: ['POST'])]
    public function laundryAddFavorite(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->json(['error' => 'errors.unauthorized'], 403);
        }

        $laundry= $this->laundryRepository->find($id);

        if (!$laundry) {
            return $this->json(['error' => 'error.laundry_not_found'], 404);
        }

        $existing = $this->favoriteLaundryRepository->findOneBy([
            'user' => $user,
            'laundry' => $laundry,
        ]);

        if($existing) {
            return $this->json(['error' => 'error.laundry_already_in_favorites'], 409);
        }

        try {
            $favoriteLaundry = new LaundryFavorite();
            $favoriteLaundry->setLaundry($laundry);
            $favoriteLaundry->setUser($user);

            $this->entityManager->persist($favoriteLaundry);
            $this->entityManager->flush();

            return $this->json(['message' => 'laundry_favorite.add_success'], 201);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'errors.during_add_favorite_laundry',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('api/laundry/{id}/favorite/remove', name: 'laundry_favorite_remove', methods: ['POST'])]
    public function laundryRemoveFavorite(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->json(['error' => 'errors.unauthorized'], 403);
        }

        $laundry= $this->laundryRepository->find($id);

        if (null === $laundry) {
            return $this->json(['error' => 'error.laundry_not_found'], 404);
        }

        $existing = $this->favoriteLaundryRepository->findOneBy([
            'user' => $user,
            'laundry' => $laundry,
        ]);

        if (!$existing) {
            return $this->json(['error' => 'error.laundry_not_found_in_favorites'], 404);
        }

        try {
            $this->entityManager->remove($existing);
            $this->entityManager->flush();

            return $this->json(['message' => 'laundry_favorite.remove_success'], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'errors.during_remove_favorite_laundry',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}