<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SireneService
{
    private const SIRENE_API_URL = 'https://api.insee.fr/entreprises/sirene/V3.11/siret';
    private const TOKEN_URL = 'https://api.insee.fr/api-oauth/oauth2/token';
    
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $clientId,
        private string $clientSecret
    ) {}

    /**
     * Obtient un access token via OAuth2
     */
    private function getAccessToken(): ?string
    {
        // Réutiliser le token s'il n'est pas expiré
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        try {
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'auth_basic' => [$this->clientId, $this->clientSecret],
                'body' => [
                    'grant_type' => 'client_credentials',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                $this->accessToken = $data['access_token'] ?? null;
                // Token expire dans X secondes, on le garde 5 min avant expiry
                $this->tokenExpiry = time() + ($data['expires_in'] ?? 3600) - 300;
                
                return $this->accessToken;
            }
        } catch (ClientExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            // Erreur d'authentification
            return null;
        }

        return null;
    }

    /**
     * Vérifie si un SIRET existe via l'API SIRENE
     * 
     * @param string $siret Le SIRET à vérifier (14 chiffres)
     * @return array Informations sur l'entreprise ou erreur
     */
    public function verifySiret(string $siret): array
    {
        // Validation du format SIRET (14 chiffres)
        if (!preg_match('/^\d{14}$/', $siret)) {
            return [
                'valid' => false,
                'error' => 'SIRET must be 14 digits'
            ];
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return [
                'valid' => false,
                'error' => 'Unable to authenticate with INSEE API'
            ];
        }

        try {
            $response = $this->httpClient->request('GET', self::SIRENE_API_URL . '/' . $siret, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $data = $response->toArray();
                
                return [
                    'valid' => true,
                    'siret' => $data['siret'] ?? $siret,
                    'nom_complet' => $data['nom_complet'] ?? null,
                    'enseigne' => $data['enseigne'] ?? null,
                    'etat_administratif' => $data['etat_administratif'] ?? null,
                ];
            } elseif ($statusCode === 404) {
                return [
                    'valid' => false,
                    'error' => 'SIRET not found'
                ];
            } else {
                return [
                    'valid' => false,
                    'error' => 'Error checking SIRET: ' . $statusCode
                ];
            }
        } catch (ClientExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return [
                'valid' => false,
                'error' => 'Unable to verify SIRET: ' . $e->getMessage()
            ];
        }
    }
}
