<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private SerializerInterface $serializer
    ) {}

    #[Route('/all', name: 'api_users_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé : vous n\'avez pas les droits nécessaires pour consulter.')]
    public function index(Request $request): JsonResponse
    {
        $page   = max(1, $request->query->getInt('page', 1));
        $limit  = min(50, max(1, $request->query->getInt('limit', 10)));
        $offset = ($page - 1) * $limit;

        $total = $this->userRepository->count([]);
        $users = $this->userRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);

        $data = json_decode($this->serializer->serialize($users, 'json', [
            'groups' => ['user:read']
        ]), true);

        return $this->json([
            'data'  => $data,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'pages' => (int) ceil($total / $limit),
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_users_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé : vous n\'avez pas les droits nécessaires.')]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($user, Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }

    #[Route('/new', name:'app_users_create', methods:['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $newUser = $this->serializer->deserialize($request->getContent(),
            User::class,
            'json',
        );

        $errors = $this->validator->validate($newUser);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'status' => 'error',
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($newUser);
        $this->entityManager->flush();

        $location = $this->urlGenerator->generate(
            'app_user_by_id',
            ['id' => $newUser->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->json(['statut'=>'success'], Response::HTTP_OK, ['Location' => $location]);
    }


}
