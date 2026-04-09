<?php

namespace App\Controller;

use App\Enum\LaundryStatusEnum;
use App\Repository\LaundryNoteRepository;
use App\Repository\LaundryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PublicLaundryController extends AbstractController
{
    #[Route('/api/laundries/nearby', name: 'api_public_laundries_nearby', methods: ['GET'])]
    public function nearby(Request $request, LaundryRepository $laundryRepository, LaundryNoteRepository $laundryNoteRepository): JsonResponse
    {
        $latRaw = $request->query->get('lat');
        $lngRaw = $request->query->get('lng');

        $hasLat = $latRaw !== null && $latRaw !== '';
        $hasLng = $lngRaw !== null && $lngRaw !== '';

        if ($hasLat xor $hasLng) {
            return $this->json(['error' => 'lat_and_lng_required_together'], 400);
        }

        if (($hasLat && !is_numeric($latRaw)) || ($hasLng && !is_numeric($lngRaw))) {
            return $this->json(['error' => 'invalid_lat_lng'], 400);
        }

        $hasPosition = $hasLat && $hasLng;
        $latitude = $hasPosition ? (float) $latRaw : null;
        $longitude = $hasPosition ? (float) $lngRaw : null;

        $radiusKm = (float) $request->query->get('radius', 20);
        $radiusKm = max(0.5, min(100, $radiusKm));

        $limit = (int) $request->query->get('limit', 50);
        $limit = max(1, min(100, $limit));

        $city = trim((string) $request->query->get('city', ''));
        $query = trim((string) $request->query->get('query', ''));

        $laundries = $laundryRepository
            ->createQueryBuilder('l')
            ->leftJoin('l.address', 'a')
            ->leftJoin('l.logo', 'logo')
            ->addSelect('logo')
            ->addSelect('a')
            ->where('l.status = :status')
            ->andWhere('l.deletedAt IS NULL')
            ->andWhere('a.latitude IS NOT NULL')
            ->andWhere('a.longitude IS NOT NULL')
            ->setParameter('status', LaundryStatusEnum::APPROVED)
            ->getQuery()
            ->getResult();

        $results = [];

        foreach ($laundries as $laundry) {
            $address = $laundry->getAddress();
            if (!$address) {
                continue;
            }

            $laundryCity = (string) ($address->getCity() ?? '');
            $laundryAddress = (string) ($address->getAddress() ?? '');
            $laundryName = (string) ($laundry->getEstablishmentName() ?? '');
            $laundryCountry = (string) ($address->getCountry() ?? '');

            if ($city !== '' && mb_strtolower($laundryCity) !== mb_strtolower($city)) {
                continue;
            }

            if ($query !== '') {
                $haystack = mb_strtolower(trim($laundryName . ' ' . $laundryAddress . ' ' . $laundryCity . ' ' . $laundryCountry));
                if (!str_contains($haystack, mb_strtolower($query))) {
                    continue;
                }
            }

            $distanceKm = null;
            if ($hasPosition) {
                $distanceKm = $this->distanceInKm(
                    $latitude,
                    $longitude,
                    (float) $address->getLatitude(),
                    (float) $address->getLongitude()
                );

                if ($distanceKm > $radiusKm) {
                    continue;
                }
            }

                $averageNote = null;
                $reviewCount = 0;
                $stats = $laundryNoteRepository->getAverageRatingAndCountByLaundryIds([(int) $laundry->getId()]);
                if ($stats && $stats['avg_rating'] !== null) {
                    $averageNote = round((float) $stats['avg_rating'], 2);
                    $reviewCount = (int) $stats['review_count'];
                }

                $logo = $laundry->getLogo();

            $results[] = [
                'id' => $laundry->getId(),
                'establishmentName' => $laundryName,
                'description' => $laundry->getDescription() ?? '',
                'address' => $laundryAddress,
                'postalCode' => (string) ($address->getPostalCode() ?? ''),
                'city' => $laundryCity,
                'country' => $laundryCountry,
                'latitude' => $address->getLatitude(),
                'longitude' => $address->getLongitude(),
                'distanceKm' => $distanceKm,
                'rating' => $averageNote,
                'reviewCount' => $reviewCount,
                'featured' => false,
                'services' => [],
                'openingHours' => null,
                'imageUrl' => $logo?->getLocation(),
            ];
        }

        usort($results, static function (array $a, array $b): int {
            if ($a['distanceKm'] === null && $b['distanceKm'] === null) {
                return strcmp((string) $a['establishmentName'], (string) $b['establishmentName']);
            }
            if ($a['distanceKm'] === null) {
                return 1;
            }
            if ($b['distanceKm'] === null) {
                return -1;
            }
            return $a['distanceKm'] <=> $b['distanceKm'];
        });

        $results = array_slice($results, 0, $limit);

        return $this->json([
            'laundries' => $results,
            'meta' => [
                'count' => count($results),
                'hasPosition' => $hasPosition,
                'radiusKm' => $radiusKm,
                'limit' => $limit,
            ],
        ]);
    }

    private function distanceInKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
