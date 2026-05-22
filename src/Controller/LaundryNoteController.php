<?php

namespace App\Controller;

use App\Entity\Laundry;
use App\Entity\LaundryNote;
use App\Entity\User;
use App\Repository\LaundryNoteRepository;
use App\Repository\LaundryRepository;
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
        private readonly LaundryRepository $laundryRepository,
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager,
    )
    {
    }

    #[Route('/api/laundry/{id}/comment/add', name: 'api_laundry_note_add', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addLaundryComment(Request $request, int $id): JsonResponse
    {
        $user = $this->getUser();

        $laundry = $this->laundryRepository->find($id);
        if (!$laundry) {
            return $this->json(['message' => 'errors.laundry_not_found'], 404);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'errors.invalid_payload'], 400);
        }

        $errors = $this->validateLaundryNotePayload($payload);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        $laundryNote = $this->laundryNoteRepository->findOneBy(['user' => $user, 'laundry' => $laundry]);
        $isNew = $laundryNote === null;

        if ($isNew) {
            $laundryNote = new LaundryNote();
            $laundryNote->setLaundry($laundry);
            $laundryNote->setUser($user);
        }

        $laundryNote->setRating((int) $payload['note']);
        $laundryNote->setRatedAt(new \DateTime());

        $comment = $payload['comment'] ?? null;
        if ($comment !== null && $comment !== '') {
            $laundryNote->setComment($comment);
            $laundryNote->setCommentedAt(new \DateTime());
        } else {
            $laundryNote->setComment(null);
            $laundryNote->setCommentedAt(null);
        }

        if ($isNew) {
            $this->entityManager->persist($laundryNote);
        }
        $this->entityManager->flush();

        $data = $this->serializer->normalize($laundryNote, null, ['groups' => ['laundry:read']]);

        return $this->json(['laundryNote' => $data], $isNew ? 201 : 200);
    }

    #[Route('/api/laundry/{id}/comment/remove', name: 'api_laundry_note_remove', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function removeLaundryComment(int $id): JsonResponse
    {
        $user = $this->getUser();

        $laundry = $this->laundryRepository->find($id);
        if (!$laundry) {
            return $this->json(['message' => 'errors.laundry_not_found'], 404);
        }

        $laundryNote = $this->laundryNoteRepository->findOneBy(['user' => $user, 'laundry' => $laundry]);
        if ($laundryNote) {
            $this->entityManager->remove($laundryNote);
            $this->entityManager->flush();

            return $this->json(['message' => 'laundry comment removed'], 200);
        }

        return $this->json(['message' => 'errors.laundry_note_not_found'], 404);
    }

    #[Route('/api/laundry/{id}/comment/update', name: 'api_laundry_note_update', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateLaundryComment(Request $request, int $id): JsonResponse
    {
        $user = $this->getUser();

        $laundry = $this->laundryRepository->find($id);
        if (!$laundry) {
            return $this->json(['message' => 'errors.laundry_not_found'], 404);
        }

        $laundryNote = $this->laundryNoteRepository->findOneBy(['user' => $user, 'laundry' => $laundry]);
        if (!$laundryNote) {
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

        $laundryNote->setRating((int) $payload['note']);
        $laundryNote->setRatedAt(new \DateTime());

        $comment = $payload['comment'] ?? null;
        if ($comment !== null && $comment !== '') {
            $laundryNote->setComment($comment);
            $laundryNote->setCommentedAt(new \DateTime());
        } else {
            $laundryNote->setComment(null);
            $laundryNote->setCommentedAt(null);
        }

        $this->entityManager->flush();

        $data = $this->serializer->normalize($laundryNote, null, ['groups' => ['laundry:read']]);

        return $this->json(['laundryNote' => $data], 200);
    }

    #[Route('/api/me/comments', name: 'api_laundry_note_all_me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getLaundryNotesByUser(Request $request): JsonResponse
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

            $data = $this->serializer->normalize($comments, null, ['groups' => ['laundry:read', 'laundry:note']]);

            return JsonResponse::fromJsonString(
                json_encode([
                    'comments' => $data,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => (int) ceil($total / max(1, $limit)),
                    ],
                ])
            );
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/laundry/{id}/comments', name: 'api_laundry_note_all_laundry', methods: ['GET'])]
    public function getLaundryNotesByLaundry(Request $request, int $id): JsonResponse
    {
        try {
            $laundry = $this->laundryRepository->find($id);
            if (!$laundry) {
                return $this->json(['message' => 'errors.laundry_not_found'], 404);
            }

            $page = max(1, (int) $request->query->get('page', 1));
            $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
            $offset = ($page - 1) * $limit;

            $comments = $this->laundryNoteRepository->getCommentsByLaundry($laundry, $offset, $limit);
            $total = $this->laundryNoteRepository->countCommentsByLaundry($laundry);
            $average = $this->laundryNoteRepository->getAverageRatingByLaundry($laundry);

            $data = $this->serializer->normalize($comments, null, ['groups' => ['laundry:read']]);

            return JsonResponse::fromJsonString(
                json_encode([
                    'comments' => $data,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'average' => $average !== null ? round((float) $average, 2) : null,
                        'total' => $total,
                        'pages' => (int) ceil($total / max(1, $limit)),
                    ],
                ])
            );
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function validateLaundryNotePayload(array $payload): array
    {
        $errors = [];

        $note = $payload['note'] ?? null;
        $comment = $payload['comment'] ?? null;

        if ($note === null || $note === '') {
            $errors['note'] = 'validation.note_required';
        } elseif (!is_int($note) && !ctype_digit((string) $note)) {
            $errors['note'] = 'validation.note_invalid_format';
        } elseif ((int) $note < 1 || (int) $note > 5) {
            $errors['note'] = 'validation.note_out_of_range';
        }

        if ($comment !== null && strlen($comment) > 500) {
            $errors['comment'] = 'validation.comment_max_length';
        }

        return $errors;
    }
}
