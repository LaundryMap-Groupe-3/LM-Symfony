<?php

namespace App\Service;

use App\Entity\EmailVerificationToken;
use App\Entity\Professional;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $frontendUrl,
        private string $fromEmail,
        private string $appName = 'LaundryMap',
    ) {
    }

    /**
     * Envoie un email de vérification d'adresse email
     */
    public function sendVerificationEmail(User $user, EmailVerificationToken $token): void
    {
        $verificationUrl = sprintf(
            '%s/verify-email?token=%s',
            $this->frontendUrl,
            $token->getToken()
        );

        $html = $this->renderVerificationEmail($user, $token->getToken(), $verificationUrl, $token->getExpiresAt());

        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->appName))
            ->to(new Address($user->getEmail(), ($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? '')))
            ->subject('Vérifiez votre adresse email')
            ->html($html);

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     */
    public function sendPasswordResetEmail(string $email, string $firstName, string $resetUrl): bool
    {
        try {
            $html = $this->renderPasswordResetEmail($firstName, $resetUrl);

            $emailMessage = (new Email())
                ->from(new Address($this->fromEmail, $this->appName))
                ->to(new Address($email, $firstName))
                ->subject('Réinitialiser votre mot de passe')
                ->html($html);

            $this->mailer->send($emailMessage);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function renderVerificationEmail(User $user, string $token, string $verificationUrl, \DateTimeInterface $expiresAt): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Vérifiez votre adresse email</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .container {
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 30px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            .header h1 {
                color: #2c3e50;
                margin: 0;
            }
            .content {
                margin-bottom: 30px;
            }
            .button {
                display: inline-block;
                padding: 12px 30px;
                background-color: #3498db;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
            }
            .button:hover {
                background-color: #2980b9;
            }
            .token-section {
                background-color: #ecf0f1;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
                font-family: monospace;
                word-break: break-all;
            }
            .footer {
                border-top: 1px solid #ddd;
                padding-top: 20px;
                font-size: 12px;
                color: #666;
                text-align: center;
            }
            .info-box {
                background-color: #fff3cd;
                border: 1px solid #ffc107;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Bienvenue sur LaundryMap!</h1>
            </div>

            <div class="content">
                <p>Bonjour {$user->getFirstName()},</p>

                <p>
                    Merci de votre inscription! Pour finaliser votre compte, veuillez vérifier votre adresse email 
                    en cliquant sur le bouton ci-dessous.
                </p>

                <center>
                    <a href="{$verificationUrl}" class="button">Vérifier votre email</a>
                </center>

                <div class="info-box">
                    <strong>Attention:</strong> Ce lien expire dans 24 heures.
                </div>

                <p style="margin-top: 30px; color: #666; font-size: 12px;">
                    Si vous n'avez pas créé ce compte, veuillez ignorer cet email.
                </p>
            </div>

            <div class="footer">
                <p>&copy; 2026 LaundryMap. Tous droits réservés.</p>
            </div>
        </div>
    </body>
</html>
HTML;
    }

    private function renderPasswordResetEmail(string $firstName, string $resetUrl): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Réinitialiser votre mot de passe</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .container {
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 30px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            .header h1 {
                color: #2c3e50;
                margin: 0;
            }
            .content {
                margin-bottom: 30px;
            }
            .button {
                display: inline-block;
                padding: 12px 30px;
                background-color: #3498db;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
            }
            .button:hover {
                background-color: #2980b9;
            }
            .footer {
                border-top: 1px solid #ddd;
                padding-top: 20px;
                font-size: 12px;
                color: #666;
                text-align: center;
            }
            .info-box {
                background-color: #fff3cd;
                border: 1px solid #ffc107;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
            }
            .warning-box {
                background-color: #f8d7da;
                border: 1px solid #f5c6cb;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
                color: #721c24;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Réinitialiser votre mot de passe</h1>
            </div>

            <div class="content">
                <p>Bonjour {$firstName},</p>

                <p>
                    Vous avez demandé à réinitialiser votre mot de passe. Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe.
                </p>

                <center>
                    <a href="{$resetUrl}" class="button">Réinitialiser mon mot de passe</a>
                </center>

                <div class="info-box">
                    <strong>Attention:</strong> Ce lien expire dans 24 heures.
                </div>

                <div class="warning-box">
                    <strong>Sécurité:</strong> Si vous n'avez pas fait cette demande, ignorez cet email. Votre compte reste sécurisé.
                </div>

                <p style="margin-top: 30px; color: #666; font-size: 12px;">
                    Ce lien ne peut être utilisé qu'une seule fois. Si vous n'avez pas réinitialisé votre mot de passe avant son expiration, 
                    vous devrez faire une nouvelle demande.
                </p>
            </div>

            <div class="footer">
                <p>&copy; 2026 LaundryMap. Tous droits réservés.</p>
            </div>
        </div>
    </body>
</html>
HTML;
    }

    /**
     * Envoie un email de validation du compte professionnel
     */
    public function sendProfessionalApprovalEmail(Professional $professional): bool
    {
        try {
            $user = $professional->getUser();
            $html = $this->renderProfessionalApprovalEmail($user, $professional);

            $email = (new Email())
                ->from(new Address($this->fromEmail, $this->appName))
                ->to(new Address($user->getEmail(), ($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? '')))
                ->subject('Votre compte professionnel a été validé!')
                ->html($html);

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Envoie un email de refus du compte professionnel
     */
    public function sendProfessionalRejectionEmail(Professional $professional, string $rejectionReason): bool
    {
        try {
            $user = $professional->getUser();
            $html = $this->renderProfessionalRejectionEmail($user, $professional, $rejectionReason);

            $email = (new Email())
                ->from(new Address($this->fromEmail, $this->appName))
                ->to(new Address($user->getEmail(), ($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? '')))
                ->subject('Votre compte professionnel a été refusé')
                ->html($html);

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function renderProfessionalApprovalEmail(User $user, Professional $professional): string
    {
        $companyName = $professional->getCompanyName() ?? 'Votre entreprise';

        return <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Compte professionnel validé</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .container {
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 30px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            .header h1 {
                color: #27ae60;
                margin: 0;
            }
            .content {
                margin-bottom: 30px;
            }
            .button {
                display: inline-block;
                padding: 12px 30px;
                background-color: #27ae60;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
            }
            .button:hover {
                background-color: #229954;
            }
            .success-box {
                background-color: #d4edda;
                border: 1px solid #c3e6cb;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
                color: #155724;
            }
            .footer {
                border-top: 1px solid #ddd;
                padding-top: 20px;
                font-size: 12px;
                color: #666;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>✓ Félicitations!</h1>
            </div>

            <div class="content">
                <p>Bonjour {$user->getFirstName()},</p>

                <p>
                    Nous sommes heureux de vous informer que votre compte professionnel pour <strong>{$companyName}</strong> 
                    a été validé avec succès par notre équipe d'administration!
                </p>

                <div class="success-box">
                    <strong>Votre compte est maintenant actif.</strong><br>
                    Vous pouvez dès maintenant accéder à toutes les fonctionnalités de LaundryMap pour les professionnels.
                </div>

                <p style="margin-top: 30px; color: #666; font-size: 12px;">
                    Si vous avez des questions, n'hésitez pas à nous contacter.
                </p>
            </div>

            <div class="footer">
                <p>&copy; 2026 LaundryMap. Tous droits réservés.</p>
            </div>
        </div>
    </body>
</html>
HTML;
    }

    private function renderProfessionalRejectionEmail(User $user, Professional $professional, string $rejectionReason): string
    {
        $companyName = $professional->getCompanyName() ?? 'Votre entreprise';

        return <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Décision concernant votre compte professionnel</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .container {
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 30px;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
            }
            .header h1 {
                color: #d32f2f;
                margin: 0;
            }
            .content {
                margin-bottom: 30px;
            }
            .button {
                display: inline-block;
                padding: 12px 30px;
                background-color: #3498db;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
            }
            .button:hover {
                background-color: #2980b9;
            }
            .rejection-box {
                background-color: #ffebee;
                border: 1px solid #ef9a9a;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
                color: #c62828;
            }
            .reason-section {
                background-color: #f5f5f5;
                border-left: 4px solid #d32f2f;
                padding: 15px;
                margin: 20px 0;
            }
            .info-box {
                background-color: #e3f2fd;
                border: 1px solid #90caf9;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
                color: #1565c0;
            }
            .footer {
                border-top: 1px solid #ddd;
                padding-top: 20px;
                font-size: 12px;
                color: #666;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Décision concernant votre demande</h1>
            </div>

            <div class="content">
                <p>Bonjour {$user->getFirstName()},</p>

                <p>
                    Merci de votre intérêt pour LaundryMap. Après examen attentif de votre demande de compte professionnel 
                    pour <strong>{$companyName}</strong>, nous regrettons de ne pas pouvoir valider votre compte à ce moment.
                </p>

                <div class="rejection-box">
                    <strong>Statut: Refusé</strong>
                </div>

                <h3>Raison du refus:</h3>
                <div class="reason-section">
                    {$rejectionReason}
                </div>

                <h3>Comment continuer ?</h3>
                <div class="info-box">
                    <strong>Veuillez noter que votre compte a été supprimé.</strong> Pour réessayer, vous devrez créer un nouveau compte 
                    avec les corrections apportées aux éléments qui ont conduit au refus de votre première demande.
                </div>

                <p>
                    Pour réenregistrer votre compte professionnel avec des informations corrigées ou complètes, 
                    veuillez procéder à une nouvelle inscription sur notre plateforme.
                </p>

                <p>
                    Si vous estimez que cette décision a été prise par erreur ou si vous avez des questions concernant les raisons du refus, 
                    n'hésitez pas à nous contacter. Nous serons heureux de discuter de votre situation et de vous aider.
                </p>

                <center>
                    <a href="{$this->frontendUrl}/contact" class="button">Nous contacter</a>
                </center>

                <p style="margin-top: 30px; color: #666; font-size: 12px;">
                    Cordialement,<br>
                    L'équipe LaundryMap
                </p>
            </div>

            <div class="footer">
                <p>&copy; 2026 LaundryMap. Tous droits réservés.</p>
            </div>
        </div>
    </body>
</html>
HTML;
    }
}


