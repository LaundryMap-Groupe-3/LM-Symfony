<?php

namespace App\Controller;

use App\Entity\Laundry;
use App\Entity\LaundryFavorite;
use App\Repository\LaundryFavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LaundryFavoriteController extends AbstractController
{
    #[Route('/api/laundries/{id}/favorite', name: 'api_laundry_add_favorite', methods: ['POST'])]
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

    #[Route('/api/laundries/{id}/favorite', name: 'api_laundry_remove_favorite', methods: ['DELETE'])]
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
    public function getFavorites(LaundryFavoriteRepository $favoriteRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['favorites' => []]);
        }
        $favorites = $favoriteRepository->findBy(['user' => $user]);
        $result = [];
        foreach ($favorites as $favorite) {
            $laundry = $favorite->getLaundry();
            $result[] = [
                'id' => $laundry->getId(),
                'laundryId' => $laundry->getId(),
                'name' => $laundry->getEstablishmentName(),
                // Ajoute d'autres champs si besoin
            ];
        }
        return $this->json(['favorites' => $result]);
    }
}
