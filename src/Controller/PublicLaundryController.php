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
        $serviceFilters = $this->parseCsvFilter((string) $request->query->get('services', ''));
        $paymentFilters = $this->parseCsvFilter((string) $request->query->get('payments', ''));

        $openAtRaw = trim((string) $request->query->get('openAt', ''));
        $closeAtRaw = trim((string) $request->query->get('closeAt', ''));

        if (($openAtRaw !== '' xor $closeAtRaw !== '')) {
            return $this->json(['error' => 'openAt_and_closeAt_required_together'], 400);
        }

        $openAtMinutes = null;
        $closeAtMinutes = null;

        if ($openAtRaw !== '' && $closeAtRaw !== '') {
            if (!$this->isValidTimeHHMM($openAtRaw) || !$this->isValidTimeHHMM($closeAtRaw)) {
                return $this->json(['error' => 'invalid_time_range'], 400);
            }

            $openAtMinutes = $this->timeStringToMinutes($openAtRaw);
            $closeAtMinutes = $this->timeStringToMinutes($closeAtRaw);
        }

        $laundries = $laundryRepository
            ->createQueryBuilder('l')
            ->leftJoin('l.address', 'a')
            ->leftJoin('l.logo', 'logo')
            ->leftJoin('l.laundryClosures', 'closures')
            ->leftJoin('l.laundryExceptionalClosures', 'exceptionalClosures')
            ->leftJoin('l.laundryServices', 'laundryServices')
            ->leftJoin('laundryServices.service', 'service')
            ->leftJoin('l.laundryPayments', 'laundryPayments')
            ->leftJoin('laundryPayments.paymentMethod', 'paymentMethod')
            ->addSelect('logo')
            ->addSelect('a')
            ->addSelect('closures')
            ->addSelect('exceptionalClosures')
            ->addSelect('laundryServices')
            ->addSelect('service')
            ->addSelect('laundryPayments')
            ->addSelect('paymentMethod')
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

            if ($city !== '' && mb_strtolower($laundryCity) !== mb_strtolower($city)) {
                continue;
            }

            if ($query !== '') {
                $haystack = mb_strtolower(trim($laundryName . ' ' . $laundryAddress . ' ' . $laundryCity));
                if (!str_contains($haystack, mb_strtolower($query))) {
                    continue;
                }
            }

            $services = [];
            foreach ($laundry->getLaundryServices() as $laundryService) {
                $serviceName = (string) ($laundryService->getService()?->getName() ?? '');
                if ($serviceName !== '') {
                    $services[] = $serviceName;
                }
            }

            if (!$this->matchesAllFilters($serviceFilters, $services)) {
                continue;
            }

            $payments = [];
            foreach ($laundry->getLaundryPayments() as $laundryPayment) {
                $paymentName = (string) ($laundryPayment->getPaymentMethod()?->getName() ?? '');
                if ($paymentName !== '') {
                    $payments[] = $paymentName;
                }
            }

            if (!$this->matchesAllFilters($paymentFilters, $payments)) {
                continue;
            }

            if ($openAtMinutes !== null && $closeAtMinutes !== null && !$this->isLaundryOpenBetween($laundry, $openAtMinutes, $closeAtMinutes)) {
                continue;
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
            $isOpenNow = $this->isLaundryOpenNow($laundry);

            $results[] = [
                'id' => $laundry->getId(),
                'establishmentName' => $laundryName,
                'description' => $laundry->getDescription() ?? '',
                'address' => $laundryAddress,
                'postalCode' => (string) ($address->getPostalCode() ?? ''),
                'city' => $laundryCity,
                'country' => (string) ($address->getCountry() ?? ''),
                'latitude' => $address->getLatitude(),
                'longitude' => $address->getLongitude(),
                'distanceKm' => $distanceKm,
                'rating' => $averageNote,
                'reviewCount' => $reviewCount,
                'featured' => false,
                'services' => $services,
                'paymentMethods' => $payments,
                'isOpenNow' => $isOpenNow,
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
                'serviceFilters' => $serviceFilters,
                'paymentFilters' => $paymentFilters,
                'openAt' => $openAtRaw !== '' ? $openAtRaw : null,
                'closeAt' => $closeAtRaw !== '' ? $closeAtRaw : null,
            ],
        ]);
    }

    private function parseCsvFilter(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $items = array_filter(array_map('trim', explode(',', $raw)), static fn (string $value): bool => $value !== '');

        return array_values(array_unique(array_map([$this, 'normalizeFilterToken'], $items)));
    }

    private function matchesAllFilters(array $requestedFilters, array $values): bool
    {
        if ($requestedFilters === []) {
            return true;
        }

        $normalizedValues = array_values(array_unique(array_map([$this, 'normalizeFilterToken'], $values)));

        foreach ($requestedFilters as $filter) {
            if (!in_array($filter, $normalizedValues, true)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeFilterToken(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        if (is_string($transliterated) && $transliterated !== '') {
            $normalized = $transliterated;
        }

        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');

        return match ($normalized) {
            'cb' => 'card',
            'especes' => 'cash',
            'app' => 'contactless',
            default => $normalized,
        };
    }

    private function isValidTimeHHMM(string $value): bool
    {
        return preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $value) === 1;
    }

    private function timeStringToMinutes(string $value): int
    {
        [$hours, $minutes] = explode(':', $value);

        return ((int) $hours) * 60 + (int) $minutes;
    }

    private function isLaundryOpenBetween(Laundry $laundry, int $openAtMinutes, int $closeAtMinutes): bool
    {
        $now = new \DateTimeImmutable();

        foreach ($laundry->getLaundryExceptionalClosures() as $exceptionalClosure) {
            if ($now >= $exceptionalClosure->getStartDate() && $now <= $exceptionalClosure->getEndDate()) {
                return false;
            }
        }

        $currentDay = strtolower($now->format('l'));
        $matchingSlots = [];
        $hasAnyWeeklySchedule = false;

        foreach ($laundry->getLaundryClosures() as $closure) {
            $hasAnyWeeklySchedule = true;

            if ($closure->getDay()->value !== $currentDay) {
                continue;
            }

            $start = ((int) $closure->getStartTime()->format('H')) * 60 + (int) $closure->getStartTime()->format('i');
            $end = ((int) $closure->getEndTime()->format('H')) * 60 + (int) $closure->getEndTime()->format('i');
            $matchingSlots[] = [$start, $end];
        }

        if ($matchingSlots === []) {
            return !$hasAnyWeeklySchedule;
        }

        if ($openAtMinutes > $closeAtMinutes) {
            return false;
        }

        foreach ($matchingSlots as [$slotStart, $slotEnd]) {
            if ($slotStart <= $slotEnd) {
                if ($openAtMinutes >= $slotStart && $closeAtMinutes <= $slotEnd) {
                    return true;
                }
                continue;
            }

            if ($openAtMinutes >= $slotStart || $closeAtMinutes <= $slotEnd) {
                return true;
            }
        }

        return false;
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
        $hasAnyWeeklySchedule = false;

        foreach ($laundry->getLaundryClosures() as $closure) {
            $hasAnyWeeklySchedule = true;

            if ($closure->getDay()->value !== $currentDay) {
                continue;
            }

            $hasScheduleToday = true;
            $start = ((int) $closure->getStartTime()->format('H')) * 60 + (int) $closure->getStartTime()->format('i');
            $end = ((int) $closure->getEndTime()->format('H')) * 60 + (int) $closure->getEndTime()->format('i');

            if ($start <= $end) {
                if ($currentMinutes >= $start && $currentMinutes <= $end) {
                    return true;
                }

                continue;
            }

            if ($currentMinutes >= $start || $currentMinutes <= $end) {
                return true;
            }
        }

        if ($hasScheduleToday) {
            return false;
        }

        return !$hasAnyWeeklySchedule;
    }
}
