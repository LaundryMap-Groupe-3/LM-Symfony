<?php

namespace App\Controller;

use App\Entity\Laundry;
use App\Entity\LaundryFavorite;
use App\Entity\User;
use App\Repository\LaundryFavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class LaundryFavoriteController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer
    ) {}

    #[Route('/api/laundries/{id}/favorite/add', name: 'api_laundry_add_favorite', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addFavorite(Laundry $laundry, EntityManagerInterface $em, LaundryFavoriteRepository $favoriteRepo): JsonResponse
    {
        $user = $this->getUser();
        if ($favoriteRepo->findOneBy(['user' => $user, 'laundry' => $laundry])) {
            return $this->json(['message' => 'Already in favorites'], 200);
        }
        $favorite = new LaundryFavorite();
        $favorite->setUser($user);
        $favorite->setLaundry($laundry);
        $em->persist($favorite);
        $em->flush();
        return $this->json(['message' => 'Added to favorites']);
    }

    #[Route('/api/laundries/{id}/favorite/remove', name: 'api_laundry_remove_favorite', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function removeFavorite(Laundry $laundry, EntityManagerInterface $em, LaundryFavoriteRepository $favoriteRepo): JsonResponse
    {
        $user = $this->getUser();
        $favorite = $favoriteRepo->findOneBy(['user' => $user, 'laundry' => $laundry]);
        if ($favorite) {
            $em->remove($favorite);
            $em->flush();
            return $this->json(['message' => 'Removed from favorites']);
        }
        return $this->json(['message' => 'Not in favorites'], 404);
    }

    #[Route('/api/user/favorites', name: 'api_user_favorites', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getFavorites(LaundryFavoriteRepository $favoriteRepository, Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['favorites' => []]);
            }

            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
            $offset = ($page - 1) * $limit;

            $favorites = $favoriteRepository->getFavoritesLaundriesByUser($offset, $limit, $user);
            $total = $favoriteRepository->countFavoritesLaundriesByUser($user);

            $data = $this->serializer->normalize($favorites, null,['groups' => ['favorite-laundry:read']]);

            return JsonResponse::fromJsonString(
                json_encode([
                    'laundries' => $data,
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

    #[Route('/api/laundries/favorite/ids', name: 'api_laundry_favorite_ids', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getFavoriteIds(LaundryFavoriteRepository $favoriteRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['favorites' => []]);
        }

        $favorites = $favoriteRepository->getFavoritesLaundriesIdsByUser($user);

        return $this->json(['favorites' => $favorites], 200);
    }
}
