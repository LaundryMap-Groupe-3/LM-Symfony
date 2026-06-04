<?php

namespace App\Service;

use App\Entity\EmailVerificationToken;
use App\Entity\Laundry;
use App\Entity\LaundryNote;
use App\Entity\Professional;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private string $frontendUrl,
        private string $fromEmail,
        private string $appName = 'LaundryMap',
    ) {
    }

    public function sendVerificationEmail(User $user, EmailVerificationToken $token): void
    {
        $verificationUrl = sprintf('%s/verify-email?token=%s', $this->frontendUrl, $token->getToken());

        $html = $this->twig->render('emails/verification.html.twig', [
            'firstName' => $user->getFirstName(),
            'verificationUrl' => $verificationUrl,
        ]);

        $this->send(
            $user->getEmail(),
            $this->fullName($user),
            'Vérifiez votre adresse email',
            $html
        );
    }

    public function sendPasswordResetEmail(string $email, string $firstName, string $resetUrl): bool
    {
        try {
            $html = $this->twig->render('emails/password_reset.html.twig', [
                'firstName' => $firstName,
                'resetUrl' => $resetUrl,
            ]);

            $this->send($email, $firstName, 'Réinitialiser votre mot de passe', $html);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function sendProfessionalApprovalEmail(Professional $professional): bool
    {
        try {
            $user = $professional->getUser();

            $html = $this->twig->render('emails/professional_approval.html.twig', [
                'firstName' => $user->getFirstName(),
                'companyName' => $professional->getCompanyName() ?? 'Votre entreprise',
            ]);

            $this->send(
                $user->getEmail(),
                $this->fullName($user),
                'Votre compte professionnel a été validé!',
                $html
            );
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function sendProfessionalRejectionEmail(Professional $professional, string $rejectionReason): bool
    {
        try {
            $user = $professional->getUser();

            $html = $this->twig->render('emails/professional_rejection.html.twig', [
                'firstName' => $user->getFirstName(),
                'companyName' => $professional->getCompanyName() ?? 'Votre entreprise',
                'rejectionReason' => $rejectionReason,
                'frontendUrl' => $this->frontendUrl,
            ]);

            $this->send(
                $user->getEmail(),
                $this->fullName($user),
                'Votre compte professionnel a été refusé',
                $html
            );
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function sendLaundryApprovalEmail(Laundry $laundry): bool
    {
        try {
            $user = $laundry->getProfessional()->getUser();

            $html = $this->twig->render('emails/laundry_approval.html.twig', [
                'firstName' => $user->getFirstName(),
                'laundryName' => $laundry->getEstablishmentName(),
            ]);

            $this->send(
                $user->getEmail(),
                $this->fullName($user),
                'Votre laverie a été validée!',
                $html
            );
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function sendLaundryRejectionEmail(Laundry $laundry, string $rejectionReason): bool
    {
        try {
            $user = $laundry->getProfessional()->getUser();

            $html = $this->twig->render('emails/laundry_rejection.html.twig', [
                'firstName' => $user->getFirstName(),
                'laundryName' => $laundry->getEstablishmentName(),
                'rejectionReason' => $rejectionReason,
                'frontendUrl' => $this->frontendUrl,
            ]);

            $this->send(
                $user->getEmail(),
                $this->fullName($user),
                'Votre laverie a été refusée!',
                $html
            );
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function sendReviewResponseEmail(LaundryNote $laundryNote, bool $isUpdate): void
    {
        $author = $laundryNote->getUser();
        $laundry = $laundryNote->getLaundry();

        $html = $this->twig->render('emails/review_response.html.twig', [
            'firstName' => $author->getFirstName(),
            'laundryName' => $laundry->getEstablishmentName(),
            'rating' => $laundryNote->getRating(),
            'userComment' => $laundryNote->getComment(),
            'ownerResponse' => $laundryNote->getResponse() ?? '',
            'isUpdate' => $isUpdate,
        ]);

        $subject = $isUpdate
            ? 'Le propriétaire a modifié sa réponse à votre avis'
            : 'Le propriétaire a répondu à votre avis';

        $this->send(
            $author->getEmail(),
            $this->fullName($author),
            $subject,
            $html
        );
    }

    private function send(string $toEmail, string $toName, string $subject, string $html): void
    {
        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->appName))
            ->to(new Address($toEmail, $toName))
            ->subject($subject)
            ->html($html);

        $this->mailer->send($email);
    }

    private function fullName(User $user): string
    {
        return trim(($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? ''));
    }
}
