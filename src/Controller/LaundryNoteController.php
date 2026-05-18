<?php

namespace App\Controller;

use App\Entity\Laundry;
use App\Entity\LaundryNote;
use App\Entity\User;
use App\Repository\LaundryNoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

class LaundryNoteController extends AbstractController
{
    public function __construct(
        private readonly LaundryNoteRepository $laundryNoteRepository,
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager,
    )
    {
    }

    #[Route('/api/laundry/{id}/comment/add', name: 'api_laundry_note_add', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addLaundryComment(Request $request, Laundry $laundry): JsonResponse
    {
        $user = $this->getUser();
        if($this->laundryNoteRepository->findOneBy(['user' => $user, 'laundry' => $laundry])){
            return $this->json(['message' => 'Already have comment'], 200);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'errors.invalid_payload'], 400);
        }

        $errors = $this->validateLaundryNotePayload($payload);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        $laundryNote = new LaundryNote();
        $laundryNote->setLaundry($laundry);
        $laundryNote->setUser($user);
        $laundryNote->setComment($payload['comment']);
        $laundryNote->setRating($payload['note']);

        $this->entityManager->flush();

        $data = $this->serializer->normalize($laundryNote, null, ['groups' => ['laundry:read']]);

        return $this->json(['laundryNote' => $data],201);
    }


    #[Route('/api/laundry/{id}/comment/remove', name: 'api_laundry_note_remove', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function removeLaundryComment(Laundry $laundry): JsonResponse
    {
        $user = $this->getUser();
        $laundryNote = $this->laundryNoteRepository->findOneBy(['user' => $user, 'laundry' => $laundry]);
        if($laundryNote) {
            $this->entityManager->remove($laundryNote);
            $this->entityManager->flush();

            return $this->json(['message' => 'laundry comment removed'], 200);
        }

        return $this->json(['message' => 'errors.laundry_note_not_found'], 404);
    }

    #[Route('/api/laundry/{id}/comment/{comment_id}/update', name: 'api_laundry_note_update', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateLaundryComment(Request $request, Laundry $laundry, LaundryNote $laundryNote): JsonResponse
    {
        $user = $this->getUser();
        if(!$this->laundryNoteRepository->findOneBy(['user' => $user, 'laundry' => $laundry])){
            return $this->json(['message' => 'errors.laundry_note_not_found'], 404);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'errors.invalid_payload'], 400);
        }

        $errors = $this->validateLaundryNotePayload($payload);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        $laundryNote->setComment($payload['comment']);
        $laundryNote->setRating($payload['note']);

        $this->entityManager->flush();

        $data = $this->serializer->normalize($laundryNote, null, ['groups' => ['laundry:read']]);

        return $this->json(['laundryNote' => $data], 201);
    }

    #[Route('/api/laundry/{id}/comments', name: 'api_laundry_note_all', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getLaundryNotes(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->json(['comments' => []]);
            }

            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
            $offset = ($page - 1) * $limit;

            $comments = $this->laundryNoteRepository->getCommentsByUser($user, $offset, $limit);
            $total = $this->laundryNoteRepository->countCommentsByUser($user);

            $data = $this->serializer->normalize($comments, null, ['groups' => ['laundry:read']]);

            return JsonResponse::fromJsonString(
                json_encode([
                    'comments' => $data,
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

    private function validateLaundryNotePayload(array $payload): ?array
    {
        $errors = [];

        $comment = $payload['comment'];
        $note = $payload['note'];

        if(empty($comment)) {
            $errors['comment'] = 'validation.comment_required';
        }

        if(empty($note)) {
            $errors['note'] = 'validation.note_required';
        }

        if(strlen($comment) > 500) {
            $errors['comment'] = 'validation.comment_max_length';
        }

        if(!is_int($note)) {
            $errors['note'] = 'validation.note_invalid_format';
        } elseif ($note < 0 || $note > 5) {
            $errors['note'] = 'validation.note_out_of_range';
        }

        return $errors;
    }
}
