<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WiLineService
{
    private const DEFAULT_API_URL = 'https://api.wi-line.fr';

    private string $apiUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiUser,
        private string $apiKey,
        ?string $apiUrl = null
    ) {
        $this->apiUrl = rtrim($apiUrl ?: self::DEFAULT_API_URL, '/');
    }

    /**
     * @return array{success: bool, centrale?: array<string, mixed>, error?: string, statusCode?: int, details?: string}
     */
    public function fetchCentraleDetailsByClientCode(int $clientCode): array
    {
        if ($clientCode <= 0) {
            return [
                'success' => false,
                'error' => 'validation.wiline_client_code_invalid',
            ];
        }

        if ($this->apiUser === '' || $this->apiKey === '' || $this->apiUrl === '') {
            return [
                'success' => false,
                'error' => 'errors.wiline_configuration_missing',
            ];
        }

        $authResult = $this->authenticate();
        if (!$authResult['success']) {
            return $authResult;
        }

        try {
            $response = $this->httpClient->request('GET', sprintf('%s/laundry_map/centrales/%d', $this->apiUrl, $clientCode), [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => sprintf('Bearer %s', $authResult['token']),
                ],
                'timeout' => 12,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $body = $response->toArray(false);

                if (!is_array($body)) {
                    return [
                        'success' => false,
                        'error' => 'errors.wiline_fetch_failed',
                        'statusCode' => 502,
                        'details' => 'Unexpected WI-LINE response format.',
                    ];
                }

                $responseStatus = $body['status'] ?? null;
                if ($responseStatus === false) {
                    return [
                        'success' => false,
                        'error' => 'errors.wiline_client_not_found',
                        'statusCode' => 404,
                        'details' => isset($body['message']) ? (string) $body['message'] : null,
                    ];
                }

                $centraleId = $body['id'] ?? null;
                if (!is_numeric($centraleId) || (int) $centraleId !== $clientCode) {
                    return [
                        'success' => false,
                        'error' => 'errors.wiline_client_not_found',
                        'statusCode' => 404,
                    ];
                }

                return [
                    'success' => true,
                    'centrale' => $body,
                ];
            }

            if ($statusCode === 404) {
                return [
                    'success' => false,
                    'error' => 'errors.wiline_client_not_found',
                    'statusCode' => $statusCode,
                ];
            }

            return [
                'success' => false,
                'error' => 'errors.wiline_fetch_failed',
                'statusCode' => $statusCode,
                'details' => trim(substr($response->getContent(false), 0, 240)),
            ];
        } catch (ClientExceptionInterface|ServerExceptionInterface|TransportExceptionInterface|\Throwable $exception) {
            return [
                'success' => false,
                'error' => 'errors.wiline_fetch_failed',
                'details' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{success: bool, token?: string, error?: string, statusCode?: int, details?: string}
     */
    private function authenticate(): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl . '/auth', [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'body' => [
                    'user' => $this->apiUser,
                    'api_key' => $this->apiKey,
                ],
                'timeout' => 12,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                return [
                    'success' => false,
                    'error' => 'errors.wiline_auth_failed',
                    'statusCode' => $statusCode,
                    'details' => trim(substr($response->getContent(false), 0, 240)),
                ];
            }

            $body = $response->toArray(false);
            $token = isset($body['token']) ? trim((string) $body['token']) : '';
            if ($token === '') {
                return [
                    'success' => false,
                    'error' => 'errors.wiline_auth_failed',
                    'details' => 'Token missing in response.',
                ];
            }

            return [
                'success' => true,
                'token' => $token,
            ];
        } catch (ClientExceptionInterface|ServerExceptionInterface|TransportExceptionInterface|\Throwable $exception) {
            return [
                'success' => false,
                'error' => 'errors.wiline_auth_failed',
                'details' => $exception->getMessage(),
            ];
        }
    }
}
