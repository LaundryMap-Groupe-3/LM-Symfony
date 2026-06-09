<?php

namespace App\Controller;

use App\Repository\OffensiveWordRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ContentModerationController extends AbstractController
{
    #[Route('/api/offensive-words', name: 'api_offensive_words_list', methods: ['GET'])]
    public function getWords(OffensiveWordRepository $offensiveWordRepository): JsonResponse
    {
        return $this->json(['words' => $offensiveWordRepository->findAllLabels()]);
    }
}
