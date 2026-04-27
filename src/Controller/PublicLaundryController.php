<?php

namespace App\Controller;

use App\Entity\Laundry;
use App\Enum\LaundryStatusEnum;
use App\Repository\LaundryNoteRepository;
use App\Repository\LaundryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PublicLaundryController extends AbstractController
{
    #[Route('/api/laundries/{id}', name: 'api_public_laundries_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, LaundryRepository $laundryRepository, LaundryNoteRepository $laundryNoteRepository): JsonResponse
    {
        $laundry = $laundryRepository->find($id);

        if (!$laundry || $laundry->getDeletedAt() !== null || $laundry->getStatus() !== LaundryStatusEnum::APPROVED) {
            return $this->json(['error' => 'not_found'], 404);
        }

        $address = $laundry->getAddress();
        if (!$address) {
            return $this->json(['error' => 'no_address'], 404);
        }

        $logo = $laundry->getLogo();

        // 🔒 FIX stats sécurisé
        $averageNote = null;
        $reviewCount = 0;

        $stats = $laundryNoteRepository->getAverageRatingAndCountByLaundryIds([(int) $laundry->getId()]);

        if (!empty($stats)) {
            $row = isset($stats[0]) ? $stats[0] : $stats;

            if (isset($row['avg_rating'])) {
                $averageNote = round((float) $row['avg_rating'], 2);
                $reviewCount = (int) ($row['review_count'] ?? 0);
            }
        }

        return $this->json([
            'id' => $laundry->getId(),
            'establishmentName' => $laundry->getEstablishmentName() ?? '',
            'description' => $laundry->getDescription() ?? '',
            'address' => $address->getAddress() ?? '',
            'postalCode' => (string) ($address->getPostalCode() ?? ''),
            'city' => (string) ($address->getCity() ?? ''),
            'country' => (string) ($address->getCountry() ?? ''),
            'latitude' => $address->getLatitude(),
            'longitude' => $address->getLongitude(),
            'rating' => $averageNote,
            'reviewCount' => $reviewCount,
            'isOpen' => $this->isLaundryOpenNow($laundry),
            'logo' => $logo && method_exists($logo, 'getLocation') ? $logo->getLocation() : null,
            'createdAt' => $laundry->getCreatedAt()?->format(DATE_ATOM),
            'equipment' => array_map(function ($eq) {
                return [
                    'id'       => $eq->getId(),
                    'name'     => $eq->getName(),
                    'type'     => $eq->getType(),
                    'capacity' => $eq->getCapacity(),
                    'price'    => $eq->getPrice(),
                    'duration' => $eq->getDuration(),
                ];
            }, $laundry->getLaundryEquipments()->toArray()),
            'medias' => $laundry->getLaundryMedias()
                ? array_map(function ($laundryMedia) {
                    $media = $laundryMedia->getMedia();
                    return [
                        'id' => method_exists($media, 'getId') ? $media->getId() : null,
                        'location' => method_exists($media, 'getLocation') ? $media->getLocation() : null,
                        'originalName' => method_exists($media, 'getOriginalName') ? $media->getOriginalName() : null,
                    ];
                }, $laundry->getLaundryMedias()->toArray())
                : [],
        ]);
    }

    #[Route('/api/laundries/nearby', name: 'api_public_laundries_nearby', methods: ['GET'])]
    public function nearby(Request $request, LaundryRepository $laundryRepository, LaundryNoteRepository $laundryNoteRepository): JsonResponse
    {
        try {
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

            $radiusKm = max(0.5, min(100, (float) $request->query->get('radius', 20)));
            $limit = max(1, min(100, (int) $request->query->get('limit', 50)));

            $city = trim((string) $request->query->get('city', ''));
            $query = trim((string) $request->query->get('query', ''));

            $laundries = $laundryRepository
                ->createQueryBuilder('l')
                ->leftJoin('l.address', 'a')->addSelect('a')
                ->leftJoin('l.logo', 'logo')->addSelect('logo')
                ->leftJoin('l.laundryClosures', 'closures')->addSelect('closures')
                ->leftJoin('l.laundryExceptionalClosures', 'exceptionalClosures')->addSelect('exceptionalClosures')
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
                if (!$address) continue;

                $laundryCity = (string) ($address->getCity() ?? '');
                $laundryAddress = (string) ($address->getAddress() ?? '');
                $laundryName = (string) ($laundry->getEstablishmentName() ?? '');
                $laundryCountry = (string) ($address->getCountry() ?? '');

                if ($city !== '' && mb_strtolower($laundryCity) !== mb_strtolower($city)) {
                    continue;
                }

                if ($query !== '') {
                    $haystack = mb_strtolower($laundryName . ' ' . $laundryAddress . ' ' . $laundryCity . ' ' . $laundryCountry);
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

                    if ($distanceKm > $radiusKm) continue;
                }

                // 🔒 FIX stats sécurisé
                $averageNote = null;
                $reviewCount = 0;

                $stats = $laundryNoteRepository->getAverageRatingAndCountByLaundryIds([(int) $laundry->getId()]);

                if (!empty($stats)) {
                    $row = isset($stats[0]) ? $stats[0] : $stats;

                    if (isset($row['avg_rating'])) {
                        $averageNote = round((float) $row['avg_rating'], 2);
                        $reviewCount = (int) ($row['review_count'] ?? 0);
                    }
                }

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
                    'isOpenNow' => $this->isLaundryOpenNow($laundry),
                    'imageUrl' => $laundry->getLogo()?->getLocation(),
                ];
            }

            usort($results, fn ($a, $b) =>
                ($a['distanceKm'] ?? PHP_FLOAT_MAX) <=> ($b['distanceKm'] ?? PHP_FLOAT_MAX)
            );

            return $this->json([
                'laundries' => array_slice($results, 0, $limit),
                'meta' => [
                    'count' => count($results),
                    'hasPosition' => $hasPosition,
                    'radiusKm' => $radiusKm,
                    'limit' => $limit,
                ],
            ]);

        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'internal_error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function distanceInKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function isLaundryOpenNow(Laundry $laundry): bool
    {
        $now = new \DateTimeImmutable();

        foreach ($laundry->getLaundryExceptionalClosures() as $exceptionalClosure) {
            if ($now >= $exceptionalClosure->getStartDate() && $now <= $exceptionalClosure->getEndDate()) {
                return false;
            }
        }

        $currentDay = strtolower($now->format('l'));
        $currentMinutes = ((int) $now->format('H')) * 60 + (int) $now->format('i');
        $hasScheduleToday = false;

        foreach ($laundry->getLaundryClosures() as $closure) {
            if (!$closure->getDay()) continue; 

            if ($closure->getDay()->value !== $currentDay) continue;

            $hasScheduleToday = true;

            $start = ((int) $closure->getStartTime()->format('H')) * 60 + (int) $closure->getStartTime()->format('i');
            $end = ((int) $closure->getEndTime()->format('H')) * 60 + (int) $closure->getEndTime()->format('i');

            if ($start <= $end) {
                if ($currentMinutes >= $start && $currentMinutes <= $end) return true;
            } else {
                if ($currentMinutes >= $start || $currentMinutes <= $end) return true;
            }
        }

        return !$hasScheduleToday;
    }
}