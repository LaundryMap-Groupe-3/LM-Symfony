<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SireneService
{
    private const DEFAULT_SIRENE_API_URL = 'https://api.insee.fr/api-sirene/3.11/siren';

    private string $sireneApiUrl;
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey,
        ?string $sireneApiUrl = null
    ) {
        $this->sireneApiUrl = $sireneApiUrl ?: self::DEFAULT_SIRENE_API_URL;
    }

    public function verifySiren(string $siren): array
    {
        // Validation du format SIREN (9 chiffres)
        if (!preg_match('/^\d{9}$/', $siren)) {
            return [
                'valid' => false,
                'error' => 'validation.siren_invalid'
            ];
        }

        if ($this->apiKey === '') {
            return [
                'valid' => false,
                'error' => 'errors.insee_api_key_missing'
            ];
        }

        try {
            $response = $this->httpClient->request('GET', $this->sireneApiUrl . '/' . $siren, [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-INSEE-Api-Key-Integration' => $this->apiKey,
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $data = $response->toArray();
                $uniteLegale = $data['uniteLegale'] ?? [];
                $periode = $uniteLegale['periodesUniteLegale'][0] ?? [];

                return [
                    'valid' => true,
                    'siren' => $uniteLegale['siren'] ?? $siren,
                    'nom_complet' => $periode['denominationUniteLegale']
                        ?? trim(($uniteLegale['prenom1UniteLegale'] ?? '') . ' ' . ($uniteLegale['nomUniteLegale'] ?? ''))
                        ?: null,
                    'etat_administratif' => $periode['etatAdministratifUniteLegale'] ?? null,
                ];
            }

            if ($statusCode === 404) {
                return [
                    'valid' => false,
                    'error' => 'errors.siren_not_found'
                ];
            }

            $body = trim(substr($response->getContent(false), 0, 200));
            return [
                'valid' => false,
                'error' => 'errors.siren_check_error',
                'statusCode' => $statusCode,
                'body' => $body
            ];
        } catch (ClientExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return [
                'valid' => false,
                'error' => 'errors.siren_verify_error',
                'message' => $e->getMessage()
            ];
        }
    }

}
