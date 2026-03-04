<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private SerializerInterface $serializer
    ) {}

    #[Route('/all', name: 'api_users_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/all',
        summary: 'Liste des utilisateurs avec pagination',
        tags: ['Users'],
    )]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé : vous n\'avez pas les droits nécessaires pour consulter.')]
    #[OA\Parameter(name: 'page',  in: 'query', schema: new OA\Schema(type: 'integer', default: 1))]
    #[OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10))]
    #[OA\Response(
        response: 200,
        description: 'Liste paginée des utilisateurs',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'data',  type: 'array', items: new OA\Items(ref: new Model(type: User::class))),
                new OA\Property(property: 'total', type: 'integer', example: 1),
                new OA\Property(property: 'page',  type: 'integer', example: 1),
                new OA\Property(property: 'limit', type: 'integer', example: 10),
                new OA\Property(property: 'pages', type: 'integer', example: 1),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[OA\Response(response: 403, description: 'Accès refusé')]
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
    #[OA\Get(
        path: '/api/users/{id}',
        summary: 'Récupère un utilisateur spécifique',
        tags: ['Users'],
    )]
    #[IsGranted('ROLE_ADMIN', message: 'Accès refusé : vous n\'avez pas les droits nécessaires.')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: 'Utilisateur trouvé',
        content: new OA\JsonContent(ref: '#/components/schemas/User')
    )]
    #[OA\Response(response: 404, description: 'Utilisateur non trouvé')]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[OA\Response(response: 403, description: 'Accès refusé')]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($user, Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }

    #[Route('/new', name:'app_users_create', methods:['POST'])]
    #[OA\Post(
        path: '/api/users/new',
        summary: 'Créer un nouvel utilisateur',
        description: 'Crée un nouvel utilisateur avec les données fournies',
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', format: 'text', example: 'John'),
                    new OA\Property(property: 'lastName', type: 'string', format: 'text', example: 'Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, maxLength: 64, example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur créé avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Données invalides'
            ),
            new OA\Response(
                response: 401,
                description: 'Non autorisé'
            )
        ]
    )]
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