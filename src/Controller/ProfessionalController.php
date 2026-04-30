<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Laundry;
use App\Entity\LaundryClosure;
use App\Entity\LaundryNote;
use App\Entity\LaundryEquipment;
use App\Entity\LaundryMedia;
use App\Entity\LaundryPayment;
use App\Entity\LaundryService;
use App\Entity\Media;
use App\Entity\PaymentMethod;
use App\Entity\Professional;
use App\Entity\Service;
use App\Entity\User;
use App\Enum\DayOfWeekEnum;
use App\Enum\GeolocalizationStatusEnum;
use App\Enum\LaundryEquipmentTypeEnum;
use App\Enum\LaundryStatusEnum;
use App\Service\WiLineService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LaundryNoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ProfessionalController extends AbstractController
{
    #[Route('/api/professional/laundries/{id}/logo', name: 'api_professional_laundry_logo_upload', methods: ['POST'])]
    public function uploadLaundryLogo(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $laundry = $entityManager->getRepository(Laundry::class)->find($id);
        if (!$laundry || $laundry->getProfessional()?->getId() !== $professional->getId()) {
            return $this->json(['error' => 'errors.laundry_not_found'], 404);
        }

        $logoFile = $request->files->get('logo');
        if (!$logoFile instanceof UploadedFile) {
            return $this->json(['errors' => ['logo' => 'validation.logo_required']], 400);
        }

        $mimeType = (string) $logoFile->getMimeType();
        $originalName = (string) $logoFile->getClientOriginalName();
        $fileWeight = (int) ($logoFile->getSize() ?? 0);
        if (!str_starts_with($mimeType, 'image/')) {
            return $this->json(['errors' => ['logo' => 'validation.logo_invalid_type']], 400);
        }

        if ($fileWeight > 5 * 1024 * 1024) {
            return $this->json(['errors' => ['logo' => 'validation.logo_too_large']], 400);
        }

        $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/laundries';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
            return $this->json(['error' => 'errors.generic_error'], 500);
        }

        $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($originalName, PATHINFO_FILENAME) ?: 'logo');
        $safeBaseName = trim((string) $safeBaseName, '-_');
        $safeBaseName = $safeBaseName !== '' ? strtolower($safeBaseName) : 'logo';
        $extension = $logoFile->guessExtension() ?: $logoFile->getClientOriginalExtension() ?: 'bin';
        $fileName = sprintf('%s-%s.%s', $safeBaseName, bin2hex(random_bytes(6)), strtolower($extension));

        try {
            $logoFile->move($uploadDirectory, $fileName);
        } catch (\Throwable $exception) {
            return $this->json(['error' => 'errors.generic_error'], 500);
        }

        $media = new Media();
        $media->setLocation('/uploads/laundries/' . $fileName);
        $media->setOriginalName($originalName !== '' ? $originalName : $fileName);
        $media->setWeight($fileWeight);
        $media->setMimeType($mimeType !== '' ? $mimeType : 'application/octet-stream');

        $laundry->setLogo($media);
        $laundry->setUpdatedAt(new \DateTime());

        $entityManager->persist($media);
        $entityManager->flush();

        return $this->json($this->formatLaundry($laundry));
    }

    #[Route('/api/professional/laundries/{id}/medias', name: 'api_professional_laundry_medias_upload', methods: ['POST'])]
    public function uploadLaundryMedias(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $laundry = $entityManager->getRepository(Laundry::class)->find($id);
        if (!$laundry || $laundry->getProfessional()?->getId() !== $professional->getId()) {
            return $this->json(['error' => 'errors.laundry_not_found'], 404);
        }

        $mediaFiles = $this->extractUploadedFiles($request, ['medias', 'medias[]', 'mediaFiles', 'mediaFiles[]']);
        if ($mediaFiles === []) {
            return $this->json(['errors' => ['medias' => 'validation.media_required']], 400);
        }

        $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/laundries';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
            return $this->json(['error' => 'errors.generic_error'], 500);
        }

        foreach ($mediaFiles as $mediaFile) {
            if (!$mediaFile instanceof UploadedFile) {
                continue;
            }

            $mimeType = (string) $mediaFile->getMimeType();
            $originalName = (string) $mediaFile->getClientOriginalName();
            $fileWeight = (int) ($mediaFile->getSize() ?? 0);

            if (!str_starts_with($mimeType, 'image/')) {
                return $this->json(['errors' => ['medias' => 'validation.logo_invalid_type']], 400);
            }

            if ($fileWeight > 8 * 1024 * 1024) {
                return $this->json(['errors' => ['medias' => 'validation.media_too_large']], 400);
            }

            $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($originalName, PATHINFO_FILENAME) ?: 'media');
            $safeBaseName = trim((string) $safeBaseName, '-_');
            $safeBaseName = $safeBaseName !== '' ? strtolower($safeBaseName) : 'media';
            $extension = $mediaFile->guessExtension() ?: $mediaFile->getClientOriginalExtension() ?: 'bin';
            $fileName = sprintf('%s-%s.%s', $safeBaseName, bin2hex(random_bytes(6)), strtolower($extension));

            try {
                $mediaFile->move($uploadDirectory, $fileName);
            } catch (\Throwable $exception) {
                return $this->json(['error' => 'errors.generic_error'], 500);
            }

            $media = new Media();
            $media->setLocation('/uploads/laundries/' . $fileName);
            $media->setOriginalName($originalName !== '' ? $originalName : $fileName);
            $media->setWeight($fileWeight);
            $media->setMimeType($mimeType !== '' ? $mimeType : 'application/octet-stream');

            $laundryMedia = new LaundryMedia();
            $laundryMedia->setLaundry($laundry);
            $laundryMedia->setMedia($media);
            $laundryMedia->setDescription('');

            $entityManager->persist($media);
            $entityManager->persist($laundryMedia);
        }

        $laundry->setUpdatedAt(new \DateTime());
        $entityManager->flush();

        return $this->json($this->formatLaundry($laundry));
    }

    #[Route('/api/professional/laundries/{id}/medias/{mediaId}', name: 'api_professional_laundry_media_delete', methods: ['DELETE'])]
    public function deleteLaundryMedia(int $id, int $mediaId, EntityManagerInterface $entityManager): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $laundry = $entityManager->getRepository(Laundry::class)->find($id);
        if (!$laundry || $laundry->getProfessional()?->getId() !== $professional->getId()) {
            return $this->json(['error' => 'errors.laundry_not_found'], 404);
        }

        $media = $entityManager->getRepository(Media::class)->find($mediaId);
        if (!$media) {
            return $this->json(['error' => 'errors.media_not_found'], 404);
        }

        $laundryMedia = $entityManager->getRepository(LaundryMedia::class)->findOneBy([
            'laundry' => $laundry,
            'media' => $media,
        ]);

        if (!$laundryMedia) {
            return $this->json(['error' => 'errors.media_not_found'], 404);
        }

        if ($laundry->getLogo()?->getId() === $media->getId()) {
            $laundry->setLogo(null);
        }

        $mediaLocation = $media->getLocation();
        $entityManager->remove($laundryMedia);
        $entityManager->remove($media);
        $laundry->setUpdatedAt(new \DateTime());
        $entityManager->flush();

        $absolutePath = $this->getParameter('kernel.project_dir') . '/public' . $mediaLocation;
        if (str_starts_with($mediaLocation, '/uploads/laundries/') && is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        return $this->json($this->formatLaundry($laundry));
    }

    #[Route('/api/professional/laundry-options', name: 'api_professional_laundry_options', methods: ['GET'])]
    public function getLaundryOptions(EntityManagerInterface $entityManager): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $services = $entityManager->getRepository(Service::class)->findBy([], ['name' => 'ASC']);
        $paymentMethods = $entityManager->getRepository(PaymentMethod::class)->findBy([], ['name' => 'ASC']);

        return $this->json([
            'services' => array_map(
                fn (Service $service): array => [
                    'id' => $service->getId(),
                    'name' => $service->getName(),
                    'translationKey' => $this->getServiceTranslationKey($service->getName()),
                ],
                $services
            ),
            'paymentMethods' => array_map(
                fn (PaymentMethod $paymentMethod): array => [
                    'id' => $paymentMethod->getId(),
                    'name' => $paymentMethod->getName(),
                    'translationKey' => $this->getPaymentMethodTranslationKey($paymentMethod->getName()),
                ],
                $paymentMethods
            ),
        ]);
    }

    #[Route('/api/professional/wiline/clients/{clientCode}/machines', name: 'api_professional_wiline_client_machines', methods: ['GET'])]
    public function getWiLineMachinesByClientCode(
        int $clientCode,
        EntityManagerInterface $entityManager,
        WiLineService $wiLineService
    ): JsonResponse {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $wiLineResponse = $wiLineService->fetchCentraleDetailsByClientCode($clientCode);
        if (($wiLineResponse['success'] ?? false) !== true) {
            $error = (string) ($wiLineResponse['error'] ?? 'errors.wiline_fetch_failed');
            $statusCode = (int) ($wiLineResponse['statusCode'] ?? 502);
            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = 502;
            }

            return $this->json([
                'error' => $error,
                'details' => $wiLineResponse['details'] ?? null,
            ], $statusCode);
        }

        $centrale = is_array($wiLineResponse['centrale'] ?? null) ? $wiLineResponse['centrale'] : [];
        $machines = is_array($centrale['machines'] ?? null) ? $centrale['machines'] : [];
        $machineFields = $this->buildMachineFieldsFromWiLine($machines);

        return $this->json([
            'clientCode' => $clientCode,
            'laundry' => [
                'id' => $centrale['id'] ?? null,
                'name' => $centrale['name'] ?? null,
                'serial' => $centrale['serial'] ?? null,
            ],
            'machines' => $machines,
            'autoFill' => $machineFields,
        ]);
    }

    #[Route('/api/professional/laundries', name: 'api_professional_laundry_create', methods: ['POST'])]
    public function createLaundry(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'errors.invalid_payload'], 400);
        }

        $errors = $this->validateLaundryPayload($payload, true);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        $laundry = new Laundry();
        $laundry->setProfessional($professional);
        $laundry->setStatus(LaundryStatusEnum::PENDING);
        $laundry->setCreatedAt(new \DateTime());

        $address = new Address();
        $address->setAddress('');
        $address->setStreet('');
        $address->setPostalCode(0);
        $address->setCity('');
        $address->setCountry('');
        $address->setGeolocalizationStatus(GeolocalizationStatusEnum::PENDING);
        $laundry->setAddress($address);

        $this->applyLaundryPayload($payload, $laundry, $professional, $entityManager);

        $entityManager->persist($address);
        $entityManager->persist($laundry);
        $entityManager->flush();

        return $this->json($this->formatLaundry($laundry), 201);
    }

    #[Route('/api/professional/laundries/{id}', name: 'api_professional_laundry_show', methods: ['GET'])]
    public function getLaundry(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $laundry = $entityManager->getRepository(Laundry::class)->find($id);
        if (
            !$laundry
            || $laundry->getProfessional()?->getId() !== $professional->getId()
            || $laundry->getDeletedAt() !== null
        ) {
            return $this->json(['error' => 'errors.laundry_not_found'], 404);
        }

        return $this->json($this->formatLaundry($laundry));
    }

    #[Route('/api/professional/laundries/{id}/reviews', name: 'api_professional_laundry_reviews', methods: ['GET'])]
    public function getLaundryReviews(int $id, EntityManagerInterface $entityManager, LaundryNoteRepository $laundryNoteRepository): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $laundry = $entityManager->getRepository(Laundry::class)->find($id);
        if (
            !$laundry
            || $laundry->getProfessional()?->getId() !== $professional->getId()
            || $laundry->getDeletedAt() !== null
        ) {
            return $this->json(['error' => 'errors.laundry_not_found'], 404);
        }

        $notes = $laundryNoteRepository->findPublicReviewsByLaundryId((int) $laundry->getId(), 50);
        $reviews = array_map(fn (LaundryNote $note): array => $this->formatLaundryReview($note), $notes);

        return $this->json([
            'laundryId' => $laundry->getId(),
            'reviews' => $reviews,
            'meta' => [
                'count' => count($reviews),
            ],
        ]);
    }

    #[Route('/api/professional/laundries/{laundryId}/reviews/{reviewId}/response', name: 'api_professional_laundry_review_response', methods: ['PUT'])]
    public function updateLaundryReviewResponse(
        int $laundryId,
        int $reviewId,
        Request $request,
        EntityManagerInterface $entityManager,
        LaundryNoteRepository $laundryNoteRepository
    ): JsonResponse {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $laundry = $entityManager->getRepository(Laundry::class)->find($laundryId);
        if (
            !$laundry
            || $laundry->getProfessional()?->getId() !== $professional->getId()
            || $laundry->getDeletedAt() !== null
        ) {
            return $this->json(['error' => 'errors.laundry_not_found'], 404);
        }

        $review = $laundryNoteRepository->find($reviewId);
        if (
            !$review
            || $review->getLaundry()->getId() !== $laundry->getId()
            || $review->getCommentDeletedAt() !== null
            || trim((string) $review->getComment()) === ''
        ) {
            return $this->json(['error' => 'errors.laundry_not_found'], 404);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'errors.invalid_payload'], 400);
        }

        $response = trim((string) ($payload['response'] ?? ''));
        if ($response === '') {
            return $this->json(['errors' => ['response' => 'validation.response_required']], 400);
        }

        if (mb_strlen($response) > 500) {
            return $this->json(['errors' => ['response' => 'validation.response_max_length']], 400);
        }

        $review->setResponse($response);
        $review->setRespondedAt(new \DateTime());
        $entityManager->flush();

        return $this->json([
            'review' => $this->formatLaundryReview($review),
        ]);
    }

    #[Route('/api/professional/laundries/{id}', name: 'api_professional_laundry_update', methods: ['PUT'])]
    public function updateLaundry(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $laundry = $entityManager->getRepository(Laundry::class)->find($id);
        if (
            !$laundry
            || $laundry->getProfessional()?->getId() !== $professional->getId()
            || $laundry->getDeletedAt() !== null
        ) {
            return $this->json(['error' => 'errors.laundry_not_found'], 404);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'errors.invalid_payload'], 400);
        }

        $errors = $this->validateLaundryPayload($payload, false);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        $wasRejected = $laundry->getStatus() === LaundryStatusEnum::REJECTED;
        $this->applyLaundryPayload($payload, $laundry, $professional, $entityManager);
        if ($wasRejected) {
            $laundry->setStatus(LaundryStatusEnum::PENDING);
        }
        $entityManager->flush();

        return $this->json($this->formatLaundry($laundry));
    }

    #[Route('/api/professional/laundries/{id}', name: 'api_professional_laundry_delete', methods: ['DELETE'])]
    public function deleteLaundry(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $laundry = $entityManager->getRepository(Laundry::class)->find($id);
        if (
            !$laundry
            || $laundry->getProfessional()?->getId() !== $professional->getId()
            || $laundry->getDeletedAt() !== null
        ) {
            return $this->json(['error' => 'errors.laundry_not_found'], 404);
        }

        $laundry->setDeletedAt(new \DateTime());
        $laundry->setUpdatedAt(new \DateTime());
        $entityManager->flush();

        return $this->json([
            'message' => 'Laundry deleted successfully',
            'id' => $laundry->getId(),
        ]);
    }

    #[Route('/api/professional/laundries', name: 'api_professional_laundries', methods: ['GET'])]
    public function getLaundries(EntityManagerInterface $entityManager, LaundryNoteRepository $laundryNoteRepository): JsonResponse
    {
        $professional = $this->getCurrentProfessional($entityManager);
        if ($professional === null) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        $laundries = $professional->getLaundries();
        $result = [];
        $total = 0;
        $approved = 0;
        $pending = 0;
        $rejected = 0;
        $laundryIds = [];
        foreach ($laundries as $laundry) {
            if ($laundry->getDeletedAt() !== null) {
                continue;
            }

            $address = $laundry->getAddress();
            $status = $laundry->getStatus()?->value ?? '';
            $result[] = [
                'id' => $laundry->getId(),
                'establishmentName' => $laundry->getEstablishmentName() ?? '',
                'status' => $status,
                'address' => $address?->getAddress() ?? '',
                'postalCode' => $address?->getPostalCode() ?? '',
                'city' => $address?->getCity() ?? '',
                'createdAt' => $laundry->getCreatedAt()?->format('c') ?? '',
                'updatedAt' => $laundry->getUpdatedAt()?->format('c') ?? '',
            ];
            $total++;
            $laundryIds[] = $laundry->getId();
            if ($status === 'approved') {
                $approved++;
            } elseif ($status === 'pending') {
                $pending++;
            } elseif ($status === 'rejected') {
                $rejected++;
            }
        }

        // Calcul via le repository
        $averageNote = null;
        $reviewCount = 0;
        if (count($laundryIds) > 0) {
            $stats = $laundryNoteRepository->getAverageRatingAndCountByLaundryIds($laundryIds);
            if ($stats && $stats['avg_rating'] !== null) {
                $averageNote = round((float)$stats['avg_rating'], 2);
                $reviewCount = (int)$stats['review_count'];
            }
        }

        $stats = [
            'total' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
            'all' => $total,
            'averageNote' => $averageNote,
            'reviewCount' => $reviewCount,
        ];

        return $this->json([
            'laundries' => $result,
            'stats' => $stats
        ]);
    }

    private function getCurrentProfessional(EntityManagerInterface $entityManager): ?Professional
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return null;
        }

        return $entityManager->getRepository(Professional::class)->findOneBy(['user' => $user]);
    }

    private function formatLaundryReview(LaundryNote $note): array
    {
        $user = $note->getUser();
        $firstName = trim((string) ($user->getFirstName() ?? ''));
        $lastName = trim((string) ($user->getLastName() ?? ''));
        $author = 'Anonyme';

        if ($firstName !== '' || $lastName !== '') {
            $author = trim($firstName . ' ' . ($lastName !== '' ? mb_strtoupper($lastName) : ''));
        }

        return [
            'id' => $note->getId(),
            'author' => $author,
            'rating' => $note->getRating(),
            'comment' => $note->getComment(),
            'commentedAt' => $note->getCommentedAt()?->format(DATE_ATOM),
            'response' => $note->getResponse(),
            'respondedAt' => $note->getRespondedAt()?->format(DATE_ATOM),
        ];
    }

    private function validateLaundryPayload(array $payload, bool $isCreate): array
    {
        $errors = [];

        $establishmentName = trim((string) ($payload['establishmentName'] ?? ''));
        if ($isCreate && $establishmentName == '') {
            $errors['establishmentName'] = 'validation.company_name_required';
        } elseif ($establishmentName !== '' && mb_strlen($establishmentName) > 50) {
            $errors['establishmentName'] = 'validation.company_name_max_length';
        }

        if (isset($payload['contactPhone']) && $payload['contactPhone'] !== null) {
            $phone = trim((string) $payload['contactPhone']);
            if ($phone !== '' && !preg_match('/^[0-9+\s().-]{10,20}$/', $phone)) {
                $errors['contactPhone'] = 'validation.phone_invalid';
            }
        }

        $showPreciseAddress = filter_var($payload['showPreciseAddress'] ?? false, FILTER_VALIDATE_BOOL);
        if ($showPreciseAddress) {
            $wiLineReference = trim((string) ($payload['wiLineReference'] ?? ''));
            if ($wiLineReference === '' || !preg_match('/^\d+$/', $wiLineReference)) {
                $errors['wiLineReference'] = 'validation.wiline_client_code_invalid';
            }
        }

        $address = $payload['address'] ?? [];
        if ($isCreate && !is_array($address)) {
            $errors['address'] = 'validation.address_required';
            return $errors;
        }

        if (is_array($address)) {
            $street = trim((string) ($address['street'] ?? ''));
            $city = trim((string) ($address['city'] ?? ''));
            $country = trim((string) ($address['country'] ?? ''));
            $postalCodeRaw = (string) ($address['postalCode'] ?? '');

            if ($isCreate && $street === '') {
                $errors['street'] = 'validation.street_required';
            }
            if ($isCreate && $city === '') {
                $errors['city'] = 'validation.city_required';
            }
            if ($isCreate && $country === '') {
                $errors['country'] = 'validation.country_required';
            }
            if ($isCreate && $postalCodeRaw === '') {
                $errors['postalCode'] = 'validation.postal_code_required';
            }

            if ($postalCodeRaw !== '' && !preg_match('/^\d{5}$/', $postalCodeRaw)) {
                $errors['postalCode'] = 'validation.postal_code_invalid';
            }
        }

        $this->validateOpeningHoursPayload($payload, $errors);

        return $errors;
    }

    private function validateOpeningHoursPayload(array $payload, array &$errors): void
    {
        $openingHours = $payload['openingHours'] ?? null;
        $openingHoursExtra = $payload['openingHoursExtra'] ?? [];

        if ($openingHours !== null && !is_array($openingHours)) {
            $errors['openingHours'] = 'validation.opening_hours_invalid_format';
            return;
        }

        if ($openingHoursExtra !== null && !is_array($openingHoursExtra)) {
            $errors['openingHoursExtra'] = 'validation.opening_hours_invalid_format';
            return;
        }

        foreach (DayOfWeekEnum::cases() as $dayEnum) {
            $dayKey = $dayEnum->value;
            $daySlots = [];

            $primarySlot = $openingHours[$dayKey] ?? null;
            if ($primarySlot !== null && !is_array($primarySlot)) {
                $errors["openingHours.$dayKey"] = 'validation.opening_hours_invalid_format';
                continue;
            }

            if (is_array($primarySlot)) {
                $normalizedPrimary = $this->normalizeAndValidateSlot($primarySlot, "openingHours.$dayKey", $errors);
                if ($normalizedPrimary !== null) {
                    $daySlots[] = $normalizedPrimary;
                }
            }

            $extraSlots = $openingHoursExtra[$dayKey] ?? [];
            if (!is_array($extraSlots)) {
                $errors["openingHoursExtra.$dayKey"] = 'validation.opening_hours_invalid_format';
                continue;
            }

            foreach ($extraSlots as $slotIndex => $extraSlot) {
                if (!is_array($extraSlot)) {
                    $errors["openingHoursExtra.$dayKey.$slotIndex"] = 'validation.opening_hours_invalid_format';
                    continue;
                }

                $normalizedExtra = $this->normalizeAndValidateSlot(
                    $extraSlot,
                    "openingHoursExtra.$dayKey.$slotIndex",
                    $errors
                );

                if ($normalizedExtra !== null) {
                    $daySlots[] = $normalizedExtra;
                }
            }

            if (count($daySlots) > 1) {
                usort(
                    $daySlots,
                    static fn (array $a, array $b): int => strcmp($a['open'], $b['open'])
                );

                for ($i = 1; $i < count($daySlots); $i++) {
                    if ($daySlots[$i]['open'] < $daySlots[$i - 1]['close']) {
                        $errors["openingHours.$dayKey"] = 'validation.opening_hours_overlap';
                        break;
                    }
                }
            }
        }
    }

    private function normalizeAndValidateSlot(array $slot, string $fieldKey, array &$errors): ?array
    {
        $open = trim((string) ($slot['open'] ?? ''));
        $close = trim((string) ($slot['close'] ?? ''));

        if ($open === '' && $close === '') {
            return null;
        }

        if ($open === '' || $close === '') {
            $errors[$fieldKey] = 'validation.opening_hours_slot_incomplete';
            return null;
        }

        if (!$this->isValidHourFormat($open) || !$this->isValidHourFormat($close)) {
            $errors[$fieldKey] = 'validation.opening_hours_invalid_format';
            return null;
        }

        if ($open >= $close) {
            $errors[$fieldKey] = 'validation.opening_hours_order_invalid';
            return null;
        }

        return ['open' => $open, 'close' => $close];
    }

    private function isValidHourFormat(string $value): bool
    {
        return (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value);
    }

    private function getServiceTranslationKey(string $serviceName): ?string
    {
        return match (mb_strtolower(trim($serviceName))) {
            'self-service 24/7' => 'professional.laundry_form.service_self_service_24_7',
            'ironing station' => 'professional.laundry_form.service_ironing_station',
            'laundry folding' => 'professional.laundry_form.service_laundry_folding',
            default => null,
        };
    }

    private function getPaymentMethodTranslationKey(string $paymentMethodName): ?string
    {
        return match (mb_strtolower(trim($paymentMethodName))) {
            'card' => 'professional.laundry_form.payment_card',
            'cash' => 'professional.laundry_form.payment_cash',
            'contactless' => 'professional.laundry_form.payment_contactless',
            default => null,
        };
    }

    private function applyLaundryPayload(
        array $payload,
        Laundry $laundry,
        Professional $professional,
        EntityManagerInterface $entityManager
    ): void {
        if (array_key_exists('establishmentName', $payload)) {
            $laundry->setEstablishmentName(trim((string) $payload['establishmentName']));
        }

        if (array_key_exists('description', $payload)) {
            $description = trim((string) ($payload['description'] ?? ''));
            $laundry->setDescription($description !== '' ? $description : null);
        }

        if (array_key_exists('contactPhone', $payload)) {
            $contactPhone = trim((string) ($payload['contactPhone'] ?? ''));
            $professional->setPhone($contactPhone !== '' ? $contactPhone : null);
        }

        if (array_key_exists('showPreciseAddress', $payload)) {
            $showPreciseAddress = filter_var($payload['showPreciseAddress'], FILTER_VALIDATE_BOOL);
            if (!$showPreciseAddress) {
                $laundry->setWiLineReference(null);
            } elseif ($this->toIntOrNull($payload['wiLineReference'] ?? null) !== null) {
                $laundry->setWiLineReference($this->toIntOrNull($payload['wiLineReference']));
            }
        }

        if (isset($payload['address']) && is_array($payload['address'])) {
            $address = $laundry->getAddress();
            $street = trim((string) ($payload['address']['street'] ?? $address->getStreet()));
            $city = trim((string) ($payload['address']['city'] ?? $address->getCity()));
            $country = trim((string) ($payload['address']['country'] ?? $address->getCountry()));
            $postalCode = $this->toIntOrNull($payload['address']['postalCode'] ?? null);

            if ($street !== '') {
                $address->setStreet($street);
            }
            if ($city !== '') {
                $address->setCity($city);
            }
            if ($country !== '') {
                $address->setCountry($country);
            }
            if ($postalCode !== null) {
                $address->setPostalCode($postalCode);
            }

            $addressLine = trim(sprintf('%s %s %s', $address->getStreet(), (string) $address->getPostalCode(), $address->getCity()));
            $address->setAddress($addressLine);

            if (!isset($payload['address']['latitude'])) {
                $address->setLatitude(null);
            }
            if (!isset($payload['address']['longitude'])) {
                $address->setLongitude(null);
            }
            $address->setGeolocalizationStatus(GeolocalizationStatusEnum::PENDING);
        }

        $this->syncLaundryServices($laundry, $payload['serviceIds'] ?? null, $entityManager);
        $this->syncLaundryPayments($laundry, $payload['paymentMethodIds'] ?? null, $entityManager);
        $this->syncLaundryClosures($laundry, $payload, $entityManager);
        $this->syncLaundryEquipments($laundry, $payload, $entityManager);

        $laundry->setUpdatedAt(new \DateTime());
    }

    private function syncLaundryServices(Laundry $laundry, mixed $serviceIds, EntityManagerInterface $entityManager): void
    {
        if (!is_array($serviceIds)) {
            return;
        }

        $targetIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds), static fn (int $id): bool => $id > 0)));
        $targetIdMap = array_fill_keys($targetIds, true);
        $existingById = [];

        foreach ($laundry->getLaundryServices()->toArray() as $laundryService) {
            $service = $laundryService->getService();
            $serviceId = $service?->getId();
            if ($serviceId === null) {
                continue;
            }

            $existingById[$serviceId] = $laundryService;
            if (!isset($targetIdMap[$serviceId])) {
                $laundry->removeLaundryService($laundryService);
                $entityManager->remove($laundryService);
                unset($existingById[$serviceId]);
            }
        }

        if ($targetIds === []) {
            return;
        }

        $missingIds = array_values(array_filter($targetIds, static fn (int $id): bool => !isset($existingById[$id])));
        if ($missingIds === []) {
            return;
        }

        $services = $entityManager->getRepository(Service::class)->findBy(['id' => $missingIds]);
        foreach ($services as $service) {
            $laundryService = new LaundryService();
            $laundryService->setLaundry($laundry);
            $laundryService->setService($service);
            $entityManager->persist($laundryService);
        }
    }

    private function syncLaundryPayments(Laundry $laundry, mixed $paymentMethodIds, EntityManagerInterface $entityManager): void
    {
        if (!is_array($paymentMethodIds)) {
            return;
        }

        $targetIds = array_values(array_unique(array_filter(array_map('intval', $paymentMethodIds), static fn (int $id): bool => $id > 0)));
        $targetIdMap = array_fill_keys($targetIds, true);
        $existingById = [];

        foreach ($laundry->getLaundryPayments()->toArray() as $laundryPayment) {
            $paymentMethod = $laundryPayment->getPaymentMethod();
            $paymentMethodId = $paymentMethod?->getId();
            if ($paymentMethodId === null) {
                continue;
            }

            $existingById[$paymentMethodId] = $laundryPayment;
            if (!isset($targetIdMap[$paymentMethodId])) {
                $laundry->removeLaundryPayment($laundryPayment);
                $entityManager->remove($laundryPayment);
                unset($existingById[$paymentMethodId]);
            }
        }

        if ($targetIds === []) {
            return;
        }

        $missingIds = array_values(array_filter($targetIds, static fn (int $id): bool => !isset($existingById[$id])));
        if ($missingIds === []) {
            return;
        }

        $paymentMethods = $entityManager->getRepository(PaymentMethod::class)->findBy(['id' => $missingIds]);
        foreach ($paymentMethods as $paymentMethod) {
            $laundryPayment = new LaundryPayment();
            $laundryPayment->setLaundry($laundry);
            $laundryPayment->setPaymentMethod($paymentMethod);
            $entityManager->persist($laundryPayment);
        }
    }

    private function syncLaundryClosures(Laundry $laundry, array $payload, EntityManagerInterface $entityManager): void
    {
        if (!isset($payload['openingHours']) || !is_array($payload['openingHours'])) {
            return;
        }

        foreach ($laundry->getLaundryClosures()->toArray() as $closure) {
            $laundry->removeLaundryClosure($closure);
            $entityManager->remove($closure);
        }

        $extraHours = isset($payload['openingHoursExtra']) && is_array($payload['openingHoursExtra'])
            ? $payload['openingHoursExtra']
            : [];
        $now = new \DateTime();

        foreach (DayOfWeekEnum::cases() as $dayEnum) {
            $dayKey = $dayEnum->value;
            $slots = [];

            $primary = $payload['openingHours'][$dayKey] ?? null;
            if (is_array($primary) && $this->hasOpeningSlot($primary)) {
                $slots[] = $primary;
            }

            $extras = $extraHours[$dayKey] ?? [];
            if (is_array($extras)) {
                foreach ($extras as $extraSlot) {
                    if (is_array($extraSlot) && $this->hasOpeningSlot($extraSlot)) {
                        $slots[] = $extraSlot;
                    }
                }
            }

            foreach ($slots as $slot) {
                $closure = new LaundryClosure();
                $closure->setLaundry($laundry);
                $closure->setDay($dayEnum);
                $closure->setCreatedAt($now);
                $closure->setUpdatedAt($now);
                $closure->setStartTime(\DateTime::createFromFormat('H:i', (string) $slot['open']) ?: new \DateTime('00:00'));
                $closure->setEndTime(\DateTime::createFromFormat('H:i', (string) $slot['close']) ?: new \DateTime('00:00'));
                $entityManager->persist($closure);
            }
        }
    }

    private function hasOpeningSlot(array $slot): bool
    {
        $open = trim((string) ($slot['open'] ?? ''));
        $close = trim((string) ($slot['close'] ?? ''));
        return $open !== '' && $close !== '';
    }

    private function syncLaundryEquipments(Laundry $laundry, array $payload, EntityManagerInterface $entityManager): void
    {
        $hasMachinePayload = array_key_exists('washingMachines6kg', $payload)
            || array_key_exists('washingMachines8kg', $payload)
            || array_key_exists('washingMachines10kg', $payload)
            || array_key_exists('washingMachines12kgPlus', $payload)
            || array_key_exists('dryers6kg', $payload)
            || array_key_exists('dryers8kg', $payload)
            || array_key_exists('dryers10kg', $payload)
            || array_key_exists('dryers12kgPlus', $payload);

        if (!$hasMachinePayload) {
            return;
        }

        foreach ($laundry->getLaundryEquipments()->toArray() as $equipment) {
            $entityManager->remove($equipment);
        }

        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::WASHING_MACHINE, 6, $payload, 'washingMachines6kg', 'washingPrice6kg', 35, $entityManager);
        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::WASHING_MACHINE, 8, $payload, 'washingMachines8kg', 'washingPrice8kg', 35, $entityManager);
        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::WASHING_MACHINE, 10, $payload, 'washingMachines10kg', 'washingPrice10kg', 35, $entityManager);
        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::WASHING_MACHINE, 12, $payload, 'washingMachines12kgPlus', 'washingPrice12kgPlus', 35, $entityManager);

        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::DRYER, 6, $payload, 'dryers6kg', 'dryingPrice6kg', 20, $entityManager);
        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::DRYER, 8, $payload, 'dryers8kg', 'dryingPrice8kg', 20, $entityManager);
        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::DRYER, 10, $payload, 'dryers10kg', 'dryingPrice10kg', 20, $entityManager);
        $this->createEquipmentsForCapacity($laundry, LaundryEquipmentTypeEnum::DRYER, 12, $payload, 'dryers12kgPlus', 'dryingPrice12kgPlus', 20, $entityManager);
    }

    private function createEquipmentsForCapacity(
        Laundry $laundry,
        LaundryEquipmentTypeEnum $type,
        int $capacity,
        array $payload,
        string $countField,
        string $priceField,
        int $duration,
        EntityManagerInterface $entityManager
    ): void {
        $count = max(0, (int) ($payload[$countField] ?? 0));
        $price = max(0.0, (float) ($payload[$priceField] ?? 0));

        for ($i = 1; $i <= $count; $i++) {
            $equipment = new LaundryEquipment();
            $equipment->setLaundry($laundry);
            $equipment->setType($type);
            $equipment->setCapacity($capacity);
            $equipment->setPrice($price);
            $equipment->setDuration($duration);
            $equipment->setName(sprintf('%s %dkg #%d', $type === LaundryEquipmentTypeEnum::WASHING_MACHINE ? 'Washer' : 'Dryer', $capacity, $i));
            $entityManager->persist($equipment);
        }
    }

    private function toIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param array<int, array<string, mixed>> $machines
     * @return array<string, int|float|null>
     */
    private function buildMachineFieldsFromWiLine(array $machines): array
    {
        $machineCounts = [
            'washingMachines6kg' => 0,
            'washingMachines8kg' => 0,
            'washingMachines10kg' => 0,
            'washingMachines12kgPlus' => 0,
            'dryers6kg' => 0,
            'dryers8kg' => 0,
            'dryers10kg' => 0,
            'dryers12kgPlus' => 0,
        ];

        $prices = [
            'washingPrice6kg' => null,
            'washingPrice8kg' => null,
            'washingPrice10kg' => null,
            'washingPrice12kgPlus' => null,
            'dryingPrice6kg' => null,
            'dryingPrice8kg' => null,
            'dryingPrice10kg' => null,
            'dryingPrice12kgPlus' => null,
        ];

        foreach ($machines as $machine) {
            if (!is_array($machine)) {
                continue;
            }

            $categoryText = strtoupper(trim((string) ($machine['category_text'] ?? '')));
            $category = (int) ($machine['category'] ?? 0);
            $isWashingMachine = $categoryText === 'WASH' || $category === 1;
            $isDryer = $categoryText === 'DRY' || $category === 2;

            if (!$isWashingMachine && !$isDryer) {
                continue;
            }

            $capacity = $this->extractWiLineCapacity((string) ($machine['type_name'] ?? ''), (string) ($machine['name'] ?? ''));
            if ($capacity === null) {
                continue;
            }

            $capacityKey = $capacity >= 12 ? '12kgPlus' : sprintf('%dkg', $capacity);

            if ($isWashingMachine) {
                $countField = sprintf('washingMachines%s', $capacityKey);
                $priceField = sprintf('washingPrice%s', $capacityKey);
            } else {
                $countField = sprintf('dryers%s', $capacityKey);
                $priceField = sprintf('dryingPrice%s', $capacityKey);
            }

            if (!array_key_exists($countField, $machineCounts)) {
                continue;
            }

            $machineCounts[$countField]++;

            if ($prices[$priceField] === null) {
                $priceInCents = $machine['price'] ?? null;
                if (is_numeric($priceInCents)) {
                    $prices[$priceField] = round(((float) $priceInCents) / 100, 2);
                }
            }
        }

        return [
            ...$machineCounts,
            ...$prices,
        ];
    }

    private function extractWiLineCapacity(string $typeName, string $fallbackName): ?int
    {
        foreach ([$typeName, $fallbackName] as $source) {
            if ($source === '') {
                continue;
            }

            if (preg_match('/(\d+)\s*kg/i', $source, $matches) === 1) {
                $capacity = (int) ($matches[1] ?? 0);
                if ($capacity > 0) {
                    return $capacity;
                }
            }
        }

        return null;
    }

    private function extractUploadedFiles(Request $request, array $keys): array
    {
        $files = [];

        foreach ($keys as $key) {
            $value = $request->files->get($key);
            if ($value instanceof UploadedFile) {
                $files[] = $value;
                continue;
            }

            if (is_array($value)) {
                $files = [...$files, ...$this->flattenUploadedFiles($value)];
            }
        }

        if ($files !== []) {
            return $files;
        }

        foreach ($request->files->all() as $value) {
            if ($value instanceof UploadedFile) {
                $files[] = $value;
                continue;
            }

            if (is_array($value)) {
                $files = [...$files, ...$this->flattenUploadedFiles($value)];
            }
        }

        return $files;
    }

    private function flattenUploadedFiles(array $values): array
    {
        $flattened = [];

        foreach ($values as $value) {
            if ($value instanceof UploadedFile) {
                $flattened[] = $value;
                continue;
            }

            if (is_array($value)) {
                $flattened = [...$flattened, ...$this->flattenUploadedFiles($value)];
            }
        }

        return $flattened;
    }

    private function formatLaundry(Laundry $laundry): array
    {
        $address = $laundry->getAddress();
        $professional = $laundry->getProfessional();
        $logo = $laundry->getLogo();
        $medias = [];
        foreach ($laundry->getLaundryMedias() as $laundryMedia) {
            $media = $laundryMedia->getMedia();
            $medias[] = [
                'id' => $media->getId(),
                'location' => $media->getLocation(),
                'originalName' => $media->getOriginalName(),
                'mimeType' => $media->getMimeType(),
                'weight' => $media->getWeight(),
                'description' => $laundryMedia->getDescription(),
            ];
        }
        $closures = $laundry->getLaundryClosures()->toArray();
        usort(
            $closures,
            static fn (LaundryClosure $a, LaundryClosure $b): int => strcmp(
                $a->getDay()->value . $a->getStartTime()->format('H:i'),
                $b->getDay()->value . $b->getStartTime()->format('H:i')
            )
        );

        $openingHours = [
            'monday' => ['open' => '', 'close' => ''],
            'tuesday' => ['open' => '', 'close' => ''],
            'wednesday' => ['open' => '', 'close' => ''],
            'thursday' => ['open' => '', 'close' => ''],
            'friday' => ['open' => '', 'close' => ''],
            'saturday' => ['open' => '', 'close' => ''],
            'sunday' => ['open' => '', 'close' => ''],
        ];
        $openingHoursExtra = [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => [],
        ];

        foreach ($closures as $closure) {
            $day = $closure->getDay()->value;
            $slot = [
                'open' => $closure->getStartTime()->format('H:i'),
                'close' => $closure->getEndTime()->format('H:i'),
            ];

            if ($openingHours[$day]['open'] === '' && $openingHours[$day]['close'] === '') {
                $openingHours[$day] = $slot;
            } else {
                $openingHoursExtra[$day][] = $slot;
            }
        }

        $machineCounts = [
            'washingMachines6kg' => 0,
            'washingMachines8kg' => 0,
            'washingMachines10kg' => 0,
            'washingMachines12kgPlus' => 0,
            'dryers6kg' => 0,
            'dryers8kg' => 0,
            'dryers10kg' => 0,
            'dryers12kgPlus' => 0,
        ];

        $prices = [
            'washingPrice6kg' => null,
            'washingPrice8kg' => null,
            'washingPrice10kg' => null,
            'washingPrice12kgPlus' => null,
            'dryingPrice6kg' => null,
            'dryingPrice8kg' => null,
            'dryingPrice10kg' => null,
            'dryingPrice12kgPlus' => null,
        ];

        foreach ($laundry->getLaundryEquipments() as $equipment) {
            $capacity = $equipment->getCapacity();
            $capacityKey = $capacity >= 12 ? '12kgPlus' : sprintf('%dkg', $capacity);

            if ($equipment->getType() === LaundryEquipmentTypeEnum::WASHING_MACHINE) {
                $countField = sprintf('washingMachines%s', $capacityKey);
                $priceField = sprintf('washingPrice%s', $capacityKey);
            } elseif ($equipment->getType() === LaundryEquipmentTypeEnum::DRYER) {
                $countField = sprintf('dryers%s', $capacityKey);
                $priceField = sprintf('dryingPrice%s', $capacityKey);
            } else {
                continue;
            }

            if (!array_key_exists($countField, $machineCounts)) {
                continue;
            }

            $machineCounts[$countField]++;
            if ($prices[$priceField] === null) {
                $prices[$priceField] = $equipment->getPrice();
            }
        }

        $serviceIds = [];
        foreach ($laundry->getLaundryServices() as $laundryService) {
            $service = $laundryService->getService();
            if ($service !== null) {
                $serviceIds[] = $service->getId();
            }
        }

        $paymentMethodIds = [];
        foreach ($laundry->getLaundryPayments() as $laundryPayment) {
            $paymentMethod = $laundryPayment->getPaymentMethod();
            if ($paymentMethod !== null) {
                $paymentMethodIds[] = $paymentMethod->getId();
            }
        }

        return [
            'id' => $laundry->getId(),
            'establishmentName' => $laundry->getEstablishmentName() ?? '',
            'contactPhone' => $professional?->getPhone() ?? '',
            'contactEmail' => $laundry->getContactEmail() ?? '',
            'description' => $laundry->getDescription() ?? '',
            'status' => $laundry->getStatus()?->value ?? '',
            'address' => [
                'address' => $address?->getAddress() ?? '',
                'street' => $address?->getStreet() ?? '',
                'postalCode' => $address?->getPostalCode() ?? '',
                'city' => $address?->getCity() ?? '',
                'country' => $address?->getCountry() ?? '',
            ],
            'logo' => $logo ? [
                'id' => $logo->getId(),
                'location' => $logo->getLocation(),
                'originalName' => $logo->getOriginalName(),
                'mimeType' => $logo->getMimeType(),
                'weight' => $logo->getWeight(),
            ] : null,
            'medias' => $medias,
            'createdAt' => $laundry->getCreatedAt()?->format('c') ?? '',
            'updatedAt' => $laundry->getUpdatedAt()?->format('c') ?? '',
            'showPreciseAddress' => $laundry->getWiLineReference() !== null,
            'wiLineReference' => $laundry->getWiLineReference(),
            'serviceIds' => array_values(array_unique($serviceIds)),
            'paymentMethodIds' => array_values(array_unique($paymentMethodIds)),
            'openingHours' => $openingHours,
            'openingHoursExtra' => $openingHoursExtra,
            'washingMachines6kg' => $machineCounts['washingMachines6kg'],
            'washingMachines8kg' => $machineCounts['washingMachines8kg'],
            'washingMachines10kg' => $machineCounts['washingMachines10kg'],
            'washingMachines12kgPlus' => $machineCounts['washingMachines12kgPlus'],
            'dryers6kg' => $machineCounts['dryers6kg'],
            'dryers8kg' => $machineCounts['dryers8kg'],
            'dryers10kg' => $machineCounts['dryers10kg'],
            'dryers12kgPlus' => $machineCounts['dryers12kgPlus'],
            'washingPrice6kg' => $prices['washingPrice6kg'] ?? '',
            'washingPrice8kg' => $prices['washingPrice8kg'] ?? '',
            'washingPrice10kg' => $prices['washingPrice10kg'] ?? '',
            'washingPrice12kgPlus' => $prices['washingPrice12kgPlus'] ?? '',
            'dryingPrice6kg' => $prices['dryingPrice6kg'] ?? '',
            'dryingPrice8kg' => $prices['dryingPrice8kg'] ?? '',
            'dryingPrice10kg' => $prices['dryingPrice10kg'] ?? '',
            'dryingPrice12kgPlus' => $prices['dryingPrice12kgPlus'] ?? '',
        ];
    }
}
