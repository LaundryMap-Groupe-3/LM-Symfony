<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\ProfessionalInteractionHistory;
use App\Enum\InteractionActionEnum;
use App\Enum\ProfessionalStatusEnum;
use App\Repository\ProfessionalRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminController extends AbstractController
{
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
}
