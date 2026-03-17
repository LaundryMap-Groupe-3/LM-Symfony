<?php

namespace App\Service;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $appName = 'LaundrieMap',
        private string $frontendUrl = 'http://localhost',
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
            ->from(new Address('noreply@laundrietech.com', $this->appName))
            ->to(new Address($user->getEmail(), ($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? '')))
            ->subject('Vérifiez votre adresse email')
            ->html($html);

        $this->mailer->send($email);
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
}


