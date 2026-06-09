<?php

namespace App\Controller;

use App\Entity\LaundryNote;
use App\Entity\LaundryNoteReport;
use App\Entity\User;
use App\Enum\LaundryNoteReportReasonEnum;
use App\Repository\LaundryNoteReportRepository;
use App\Repository\LaundryNoteRepository;
use App\Repository\LaundryRepository;
use App\Service\ContentModerationService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class LaundryNoteController extends AbstractController
{
    public function __construct(
        private readonly LaundryNoteRepository $laundryNoteRepository,
        private readonly LaundryNoteReportRepository $laundryNoteReportRepository,
        private readonly LaundryRepository $laundryRepository,
        private NormalizerInterface $serializer,
        private EntityManagerInterface $entityManager,
        private EmailService $emailService,
        private ContentModerationService $contentModerationService,
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

            $data = $this->serializer->normalize($comments, null, ['groups' => ['laundry:read', 'laundry:note:summary']]);

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

    #[Route('/api/note/{id}/response', name: 'api_laundry_note_response_add', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function addResponse(Request $request, int $id): JsonResponse
    {
        $professional = $this->getUser();

        $laundryNote = $this->laundryNoteRepository->find($id);
        if (!$laundryNote) {
            return $this->json(['message' => 'errors.laundry_note_not_found'], 404);
        }

        $laundry = $laundryNote->getLaundry();
        if ($laundry->getProfessional()->getUser() !== $professional) {
            return $this->json(['message' => 'errors.forbidden'], 403);
        }

        if ($laundryNote->getResponse() !== null) {
            return $this->json(['message' => 'errors.response_already_exists'], 409);
        }

        $payload = json_decode($request->getContent(), true);
        $response = trim($payload['response'] ?? '');

        if ($response === '') {
            return $this->json(['errors' => ['response' => 'validation.response_required']], 400);
        }
        if (strlen($response) > 500) {
            return $this->json(['errors' => ['response' => 'validation.response_max_length']], 400);
        }
        if ($this->contentModerationService->containsOffensiveContent($response)) {
            return $this->json(['errors' => ['response' => 'validation.response_offensive_content']], 400);
        }

        $laundryNote->setResponse($response);
        $laundryNote->setRespondedAt(new \DateTime());
        $this->entityManager->flush();

        $author = $laundryNote->getUser();
        $pref = $author->getUserPreference();
        if ($pref === null || $pref->isNotifications()) {
            try {
                $this->emailService->sendReviewResponseEmail($laundryNote, false);
            } catch (\Exception) {}
        }

        $data = $this->serializer->normalize($laundryNote, null, ['groups' => ['laundry:read']]);
        return $this->json(['laundryNote' => $data], 201);
    }

    #[Route('/api/note/{id}/response', name: 'api_laundry_note_response_update', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateResponse(Request $request, int $id): JsonResponse
    {
        try {
            $professional = $this->getUser();

            $laundryNote = $this->laundryNoteRepository->find($id);
            if (!$laundryNote) {
                return $this->json(['message' => 'errors.laundry_note_not_found'], 404);
            }

            $laundry = $laundryNote->getLaundry();
            if ($laundry->getProfessional()->getUser() !== $professional) {
                return $this->json(['message' => 'errors.forbidden'], 403);
            }

            if ($laundryNote->getResponse() === null) {
                return $this->json(['message' => 'errors.response_not_found'], 404);
            }

            $payload = json_decode($request->getContent(), true);
            $response = trim($payload['response'] ?? '');

            if ($response === '') {
                return $this->json(['errors' => ['response' => 'validation.response_required']], 400);
            }
            if (strlen($response) > 500) {
                return $this->json(['errors' => ['response' => 'validation.response_max_length']], 400);
            }
            if ($this->contentModerationService->containsOffensiveContent($response)) {
                return $this->json(['errors' => ['response' => 'validation.response_offensive_content']], 400);
            }

            $laundryNote->setResponse($response);
            $laundryNote->setRespondedAt(new \DateTime());
            $this->entityManager->flush();

            $author = $laundryNote->getUser();
            $pref = $author->getUserPreference();
            if ($pref === null || $pref->isNotifications()) {
                try {
                    $this->emailService->sendReviewResponseEmail($laundryNote, true);
                } catch (\Exception) {}
            }

            $data = $this->serializer->normalize($laundryNote, null, ['groups' => ['laundry:read']]);
            return $this->json(['laundryNote' => $data], 200);
        } catch (\Exception $e) {
            return $this->json(['message' => 'errors.internal', 'detail' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/note/{id}/response', name: 'api_laundry_note_response_remove', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function removeResponse(int $id): JsonResponse
    {
        $professional = $this->getUser();

        $laundryNote = $this->laundryNoteRepository->find($id);
        if (!$laundryNote) {
            return $this->json(['message' => 'errors.laundry_note_not_found'], 404);
        }

        $laundry = $laundryNote->getLaundry();
        if ($laundry->getProfessional()->getUser() !== $professional) {
            return $this->json(['message' => 'errors.forbidden'], 403);
        }

        $laundryNote->setResponse(null);
        $laundryNote->setRespondedAt(null);
        $this->entityManager->flush();

        return $this->json(['message' => 'response removed'], 200);
    }

    #[Route('/api/note/{id}/report', name: 'api_laundry_note_report', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function reportComment(Request $request, int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'errors.unauthorized'], 403);
        }

        $laundryNote = $this->laundryNoteRepository->find($id);
        if (!$laundryNote) {
            return $this->json(['message' => 'errors.laundry_note_not_found'], 404);
        }

        if ($laundryNote->getUser() === $user) {
            return $this->json(['error' => 'errors.cannot_report_own_comment'], 403);
        }

        if ($this->laundryNoteReportRepository->findOneBy(['laundryNote' => $laundryNote, 'user' => $user])) {
            return $this->json(['error' => 'errors.report_already_exists'], 409);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'errors.invalid_payload'], 400);
        }

        $reasonValue = $payload['reason'] ?? null;
        $reason = is_string($reasonValue) ? LaundryNoteReportReasonEnum::tryFrom($reasonValue) : null;
        if ($reason === null) {
            return $this->json(['errors' => ['reason' => 'validation.reason_invalid']], 400);
        }

        $comment = $payload['comment'] ?? null;
        if ($comment !== null && $comment !== '') {
            if (strlen($comment) > 500) {
                return $this->json(['errors' => ['comment' => 'validation.comment_max_length']], 400);
            }
        } else {
            $comment = null;
        }

        $report = new LaundryNoteReport();
        $report->setLaundryNote($laundryNote);
        $report->setUser($user);
        $report->setReason($reason);
        $report->setComment($comment);
        $report->setCreatedAt(new \DateTime());

        $this->entityManager->persist($report);
        $this->entityManager->flush();

        return $this->json(['message' => 'report created'], 201);
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
        } elseif ($comment !== null && $this->contentModerationService->containsOffensiveContent($comment)) {
            $errors['comment'] = 'validation.comment_offensive_content';
        }

        return $errors;
    }
}
