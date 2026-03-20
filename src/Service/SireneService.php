<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SireneService
{
    private const DEFAULT_SIRENE_API_URL = 'https://api.insee.fr/api-sirene/3.11/siret';

    private string $sireneApiUrl;
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
        ?string $sireneApiUrl = null
    ) {
        $this->sireneApiUrl = $sireneApiUrl ?: self::DEFAULT_SIRENE_API_URL;
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
                'error' => 'validation.siret_invalid'
            ];
        }

        if ($this->apiKey === '') {
            return [
                'valid' => false,
                'error' => 'errors.insee_api_key_missing'
            ];
        }

        try {
            $response = $this->httpClient->request('GET', $this->sireneApiUrl . '/' . $siret, [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-INSEE-Api-Key-Integration' => $this->apiKey,
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $data = $response->toArray();
                $etablissement = $data['etablissement'] ?? [];
                $uniteLegale = $etablissement['uniteLegale'] ?? [];

                return [
                    'valid' => true,
                    // Support both historical flat payload and current nested payload.
                    'siret' => $data['siret'] ?? $etablissement['siret'] ?? $siret,
                    'nom_complet' => $data['nom_complet']
                        ?? $uniteLegale['denominationUniteLegale']
                        ?? trim(($uniteLegale['prenom1UniteLegale'] ?? '') . ' ' . ($uniteLegale['nomUniteLegale'] ?? ''))
                        ?: null,
                    'enseigne' => $data['enseigne'] ?? $etablissement['enseigne1Etablissement'] ?? null,
                    'etat_administratif' => $data['etat_administratif']
                        ?? $etablissement['etatAdministratifEtablissement']
                        ?? null,
                ];
            }

            if ($statusCode === 404) {
                return [
                    'valid' => false,
                    'error' => 'errors.siret_not_found'
                ];
            }

            $body = trim(substr($response->getContent(false), 0, 200));
            return [
                'valid' => false,
                'error' => 'errors.siret_check_error',
                'statusCode' => $statusCode,
                'body' => $body
            ];
        } catch (ClientExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return [
                'valid' => false,
                'error' => 'errors.siret_verify_error',
                'message' => $e->getMessage()
            ];
        }
    }

}
