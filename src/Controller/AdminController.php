<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\LaundryClosure;
use App\Entity\LaundryInteractionHistory;
use App\Entity\ProfessionalInteractionHistory;
use App\Enum\InteractionActionEnum;
use App\Enum\LaundryEquipmentTypeEnum;
use App\Enum\LaundryStatusEnum;
use App\Enum\ProfessionalStatusEnum;
use App\Repository\LaundryRepository;
use App\Repository\ProfessionalRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

class AdminController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer
    ) {}

    #[Route('/api/admin/profile', name: 'api_admin_profile_get', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || !$user instanceof Admin) {
            return $this->json(['error' => 'errors.not_authenticated'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
        ]);
    }

    #[Route('/api/admin/professionals/pending/count', name: 'api_admin_professionals_pending_count', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getPendingProfessionalsCount(
        ProfessionalRepository $professionalRepository
    ): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof Admin) {
            return $this->json(['error' => 'errors.unauthorized'], 403);
        }

        try {
            $total = $professionalRepository->countPendingProfessionals();
        } catch (\Exception $e) {
            return $this->json(['error' => 'errors.fetch_error'], 500);
        }

        return $this->json([
            'total' => $total,
            'response' => Response::HTTP_OK,
        ]);
    }

    #[Route('/api/admin/professionals/pending', name: 'api_admin_professionals_pending', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getPendingProfessionals(
        Request $request,
        ProfessionalRepository $professionalRepository
    ): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof Admin) {
            return $this->json(['error' => 'errors.unauthorized'], 403);
        }

        // Get pagination parameters
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
        $offset = ($page - 1) * $limit;

        // Get pending professionals with pagination
        $professionals = $professionalRepository->findPendingProfessionals($limit, $offset);
        $total = $professionalRepository->countPendingProfessionals();

        $data = array_map(function ($professional) {
            return [
                'id' => $professional->getId(),
                'siret' => $professional->getSiret(),
                'status' => $professional->getStatus()->value,
                'user' => [
                    'id' => $professional->getUser()->getId(),
                    'email' => $professional->getUser()->getEmail(),
                    'firstName' => $professional->getUser()->getFirstName(),
                    'lastName' => $professional->getUser()->getLastName(),
                    'createdAt' => $professional->getUser()->getCreatedAt()->format('c'),
                ],
                'address' => $professional->getAddress() ? [
                    'id' => $professional->getAddress()->getId(),
                    'street' => $professional->getAddress()->getStreet(),
                    'postalCode' => $professional->getAddress()->getPostalCode(),
                    'city' => $professional->getAddress()->getCity(),
                ] : null,
                'createdAt' => $professional->getUser()->getCreatedAt()->format('c'),
                'validationDate' => $professional->getValidationDate()?->format('c'),
            ];
        }, $professionals);

        return $this->json([
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    #[Route('/api/admin/laundries/pending/count', name: 'api_admin_laundries_pending_count', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getPendingLaundriesCount(LaundryRepository $laundryRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof Admin) {
            return $this->json(['error' => 'errors.unauthorized'], 403);
        }

        return $this->json([
            'count' => $laundryRepository->countPendingLaundries(),
            'status' => LaundryStatusEnum::PENDING->value,
        ]);
    }

    #[Route('/api/admin/laundries/pending', name: 'api_admin_laundries_pending', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getPendingLaundries(
        Request $request,
        LaundryRepository $laundryRepository
    ): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof Admin) {
            return $this->json(['error' => 'errors.unauthorized'], 403);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
        $offset = ($page - 1) * $limit;

        $laundries = $laundryRepository->findPendingLaundries($limit, $offset);
        $total = $laundryRepository->countPendingLaundries();

        $data = array_map(function ($laundry) {
            $address = $laundry->getAddress();
            $professional = $laundry->getProfessional();
            $professionalUser = $professional->getUser();

            return [
                'id' => $laundry->getId(),
                'establishmentName' => $laundry->getEstablishmentName(),
                'status' => $laundry->getStatus()->value,
                'createdAt' => $laundry->getCreatedAt()->format('c'),
                'updatedAt' => $laundry->getUpdatedAt()->format('c'),
                'address' => $address ? [
                    'id' => $address->getId(),
                    'street' => $address->getStreet(),
                    'postalCode' => $address->getPostalCode(),
                    'city' => $address->getCity(),
                ] : null,
                'professional' => [
                    'id' => $professional->getId(),
                    'companyName' => $professional->getCompanyName(),
                    'firstName' => $professionalUser->getFirstName(),
                    'lastName' => $professionalUser->getLastName(),
                    'email' => $professionalUser->getEmail(),
                ],
            ];
        }, $laundries);

        return $this->json([
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    #[Route('/api/admin/professionals/{id}', name: 'api_admin_professionals_details', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getProfessionalDetails(
        int $id,
        ProfessionalRepository $professionalRepository
    ): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof Admin) {
            return $this->json(['error' => 'errors.unauthorized'], 403);
        }

        $professional = $professionalRepository->find($id);

        if (!$professional) {
            return $this->json(['error' => 'Professional not found'], 404);
        }

        $data = [
            'id' => $professional->getId(),
            'siret' => $professional->getSiret(),
            'status' => $professional->getStatus()->value,
            'companyName' => $professional->getCompanyName(),
            'phone' => $professional->getPhone(),
            'rejectionReason' => $professional->getRejectionReason(),
            'user' => [
                'id' => $professional->getUser()->getId(),
                'email' => $professional->getUser()->getEmail(),
                'firstName' => $professional->getUser()->getFirstName(),
                'lastName' => $professional->getUser()->getLastName(),
                'createdAt' => $professional->getUser()->getCreatedAt()->format('c'),
            ],
            'address' => $professional->getAddress() ? [
                'id' => $professional->getAddress()->getId(),
                'street' => $professional->getAddress()->getStreet(),
                'postalCode' => $professional->getAddress()->getPostalCode(),
                'city' => $professional->getAddress()->getCity(),
                'country' => $professional->getAddress()->getCountry(),
            ] : null,
            'createdAt' => $professional->getUser()->getCreatedAt()->format('c'),
            'validationDate' => $professional->getValidationDate()?->format('c'),
        ];

        return $this->json(['data' => $data]);
    }

    #[Route('/api/admin/professionals/{id}/approve', name: 'api_admin_professionals_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approveProfessional(
        int $id,
        ProfessionalRepository $professionalRepository,
        EmailService $emailService,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof Admin) {
            return $this->json(['error' => 'errors.unauthorized'], 403);
        }

        $professional = $professionalRepository->find($id);

        if (!$professional) {
            return $this->json(['error' => 'Professional not found'], 404);
        }

        $professional->setStatus(ProfessionalStatusEnum::APPROVED);
        $professional->setValidationDate(new \DateTime());
        $professional->setRejectionReason(null);

        // Enregistrer l'action dans ProfessionalInteractionHistory
        $interaction = new ProfessionalInteractionHistory();
        $interaction->setAdmin($user);
        $interaction->setProfessional($professional);
        $interaction->setAction(InteractionActionEnum::APPROVE);
        $interaction->setActionReason('Professional account approved by admin');
        $interaction->setCreatedAt(new \DateTime());
        $em->persist($interaction);

        $em->flush();

        // Envoyer l'email de validation après la confirmation en base
        $emailService->sendProfessionalApprovalEmail($professional);

        return $this->json(['message' => 'Professional approved successfully']);
    }

    #[Route('/api/admin/professionals/{id}/reject', name: 'api_admin_professionals_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function rejectProfessional(
        int $id,
        Request $request,
        ProfessionalRepository $professionalRepository,
        EmailService $emailService,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof Admin) {
            return $this->json(['error' => 'errors.unauthorized'], 403);
        }

        $professional = $professionalRepository->find($id);

        if (!$professional) {
            return $this->json(['error' => 'Professional not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? null;

        if (!$reason) {
            return $this->json(['error' => 'Rejection reason is required'], 400);
        }

        // Envoyer l'email de refus AVANT la suppression (pour avoir accès aux données du professional)
        $emailService->sendProfessionalRejectionEmail($professional, $reason);

        // Soft delete toutes les blanchisseries associées
        foreach ($professional->getLaundries() as $laundry) {
            $laundry->setDeletedAt(new \DateTime());
        }

        // Supprimer les interactions d'historique (orphelins)
        foreach ($professional->getProfessionalInteractionHistories() as $interaction) {
            $em->remove($interaction);
        }

        // Supprimer le professionnel et son utilisateur associé en cascade
        $em->remove($professional);
        $em->flush();

        return $this->json(['message' => 'Professional rejected and account deleted successfully']);
    }

    #[Route('/api/admin/laundries/{id}', name: 'api_admin_laundries_details', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getLaundryDetails(
        int $id,
        LaundryRepository $laundryRepository
    ): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof Admin) {
            return $this->json(['error' => 'errors.unauthorized'], 403);
        }

        $laundry = $laundryRepository->find($id);

        if (!$laundry) {
            return $this->json(['error' => 'Laundry not found'], 404);
        }

        $address = $laundry->getAddress();
        $professional = $laundry->getProfessional();
        $professionalUser = $professional->getUser();

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
        $serviceEntries = [];
        foreach ($laundry->getLaundryServices() as $laundryService) {
            $service = $laundryService->getService();
            if ($service !== null) {
                $serviceIds[] = $service->getId();
                $serviceEntries[] = [
                    'id' => $service->getId(),
                    'name' => $service->getName(),
                    'translationKey' => $this->getServiceTranslationKey($service->getName()),
                ];
            }
        }

        $paymentMethodIds = [];
        $paymentMethodEntries = [];
        foreach ($laundry->getLaundryPayments() as $laundryPayment) {
            $paymentMethod = $laundryPayment->getPaymentMethod();
            if ($paymentMethod !== null) {
                $paymentMethodIds[] = $paymentMethod->getId();
                $paymentMethodEntries[] = [
                    'id' => $paymentMethod->getId(),
                    'name' => $paymentMethod->getName(),
                    'translationKey' => $this->getPaymentMethodTranslationKey($paymentMethod->getName()),
                ];
            }
        }

        $latestRejectionReason = null;
        $latestRejectionAt = null;
        foreach ($laundry->getLaundryInteractionHistories() as $interaction) {
            if ($interaction->getAction() !== InteractionActionEnum::REJECT) {
                continue;
            }

            if ($latestRejectionAt === null || $interaction->getCreatedAt() > $latestRejectionAt) {
                $latestRejectionAt = $interaction->getCreatedAt();
                $latestRejectionReason = $interaction->getActionReason();
            }
        }

        $data = [
            'id' => $laundry->getId(),
            'establishmentName' => $laundry->getEstablishmentName(),
            'status' => $laundry->getStatus()->value,
            'contactEmail' => $laundry->getContactEmail(),
            'description' => $laundry->getDescription(),
            'createdAt' => $laundry->getCreatedAt()->format('c'),
            'updatedAt' => $laundry->getUpdatedAt()->format('c'),
            'logo' => $laundry->getLogo() ? [
                'id' => $laundry->getLogo()->getId(),
                'location' => $laundry->getLogo()->getLocation(),
                'originalName' => $laundry->getLogo()->getOriginalName(),
                'mimeType' => $laundry->getLogo()->getMimeType(),
            ] : null,
            'medias' => array_map(static fn ($laundryMedia) => [
                'id' => $laundryMedia->getMedia()->getId(),
                'location' => $laundryMedia->getMedia()->getLocation(),
                'originalName' => $laundryMedia->getMedia()->getOriginalName(),
                'mimeType' => $laundryMedia->getMedia()->getMimeType(),
                'description' => $laundryMedia->getDescription(),
            ], $laundry->getLaundryMedias()->toArray()),
            'openingHours' => $openingHours,
            'openingHoursExtra' => $openingHoursExtra,
            'exceptionalClosures' => array_map(static fn ($closure) => [
                'startDate' => $closure->getStartDate()->format('c'),
                'endDate' => $closure->getEndDate()->format('c'),
                'reason' => $closure->getReason(),
            ], $laundry->getLaundryExceptionalClosures()->toArray()),
            'address' => $address ? [
                'id' => $address->getId(),
                'street' => $address->getStreet(),
                'postalCode' => $address->getPostalCode(),
                'city' => $address->getCity(),
                'country' => $address->getCountry(),
                'geolocalizationStatus' => $address->getGeolocalizationStatus()->value,
            ] : null,
            'professional' => [
                'id' => $professional->getId(),
                'companyName' => $professional->getCompanyName(),
                'siret' => $professional->getSiret(),
                'phone' => $professional->getPhone(),
                'user' => [
                    'id' => $professionalUser->getId(),
                    'firstName' => $professionalUser->getFirstName(),
                    'lastName' => $professionalUser->getLastName(),
                    'email' => $professionalUser->getEmail(),
                ],
            ],
            'serviceIds' => array_values(array_unique($serviceIds)),
            'paymentMethodIds' => array_values(array_unique($paymentMethodIds)),
            'services' => $serviceEntries,
            'paymentMethods' => $paymentMethodEntries,
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
            'rejectionReason' => $latestRejectionReason,
        ];

        return $this->json(['data' => $data]);
    }

    #[Route('/api/admin/laundries/{id}/approve', name: 'api_admin_laundries_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approveLaundry(
        int $id,
        LaundryRepository $laundryRepository,
        EmailService $emailService,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof Admin) {
            return $this->json(['error' => 'errors.unauthorized'], 403);
        }

        $laundry = $laundryRepository->find($id);

        if (!$laundry) {
            return $this->json(['error' => 'Laundry not found'], 404);
        }

        $laundry->setStatus(LaundryStatusEnum::APPROVED);

        // Enregistrer l'action dans LaundryInteractionHistory
        $interaction = new LaundryInteractionHistory();
        $interaction->setAdmin($user);
        $interaction->setLaundry($laundry);
        $interaction->setAction(InteractionActionEnum::APPROVE);
        $interaction->setActionReason('Laundry approved by admin');
        $interaction->setCreatedAt(new \DateTime());
        $em->persist($interaction);

        $em->flush();

        // Envoyer l'email de validation après la confirmation en base
        $emailService->sendLaundryApprovalEmail($laundry);

        return $this->json(['message' => 'laundry approved successfully']);
    }

    #[Route('/api/admin/laundries/{id}/reject', name: 'api_admin_laundries_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function rejectLaundry(
        int $id,
        Request $request,
        LaundryRepository $laundryRepository,
        EmailService $emailService,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof Admin) {
            return $this->json(['error' => 'errors.unauthorized'], 403);
        }

        $laundry = $laundryRepository->find($id);

        if (!$laundry) {
            return $this->json(['error' => 'laundry not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? null;

        if (!$reason) {
            return $this->json(['error' => 'Rejection reason is required'], 400);
        }

        $laundry->setStatus(LaundryStatusEnum::REJECTED);

        // Envoyer l'email de refus
        $emailService->sendLaundryRejectionEmail($laundry, $reason);

        // Enregistrer l'action dans LaundryInteractionHistory
        $interaction = new LaundryInteractionHistory();
        $interaction->setAdmin($user);
        $interaction->setLaundry($laundry);
        $interaction->setAction(InteractionActionEnum::REJECT);
        $interaction->setActionReason($reason);
        $interaction->setCreatedAt(new \DateTime());
        $em->persist($interaction);
        $em->flush();

        return $this->json(['message' => 'Laundry rejected successfully']);
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
}
