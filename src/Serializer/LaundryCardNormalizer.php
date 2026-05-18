<?php

namespace App\Serializer;

use App\Entity\Laundry;
use App\Repository\LaundryNoteRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class LaundryCardNormalizer implements NormalizerInterface
{
    public function __construct(
        private LaundryNoteRepository $laundryNoteRepository,
        private RequestStack $requestStack,
    ) {}

    private function absoluteUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $path;
        }
        return $request->getSchemeAndHttpHost() . '/' . ltrim($path, '/');
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Laundry && ($context['laundry_card'] ?? false);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        if (!$object instanceof Laundry) {
            throw new \InvalidArgumentException('LaundryCardNormalizer supports Laundry objects only.');
        }

        $laundry = $object;
        $id = $laundry->getId();
        $address = $laundry->getAddress();
        $logo = $laundry->getLogo();

        $distanceById = $context['distance_by_id'] ?? [];
        $ratingById = $context['rating_by_id'] ?? [];
        $reviewCountById = $context['review_count_by_id'] ?? [];
        $openNowById = $context['open_now_by_id'] ?? [];

        $distanceKm = array_key_exists($id, $distanceById) ? $distanceById[$id] : null;
        $hasRating = array_key_exists($id, $ratingById);
        $hasReviewCount = array_key_exists($id, $reviewCountById);
        $rating = $hasRating ? $ratingById[$id] : null;
        $reviewCount = $hasReviewCount ? $reviewCountById[$id] : null;

        if (!$hasRating || !$hasReviewCount) {
            $stats = $this->laundryNoteRepository->getAverageRatingAndCountByLaundryIds([$id]);
            if ($stats && $stats['avg_rating'] !== null) {
                $rating = round((float) $stats['avg_rating'], 2);
                $reviewCount = (int) $stats['review_count'];
            } else {
                $rating = null;
                $reviewCount = 0;
            }
        }

        $isOpenNow = array_key_exists($id, $openNowById)
            ? (bool) $openNowById[$id]
            : $this->isLaundryOpenNow($laundry);

        $addressData = $address ? [
            'address' => $address->getAddress(),
            'street' => $address->getStreet(),
            'postalCode' => $address->getPostalCode(),
            'city' => $address->getCity(),
            'country' => $address->getCountry(),
            'latitude' => $address->getLatitude(),
            'longitude' => $address->getLongitude(),
        ] : null;

        return [
            'id' => $id,
            'establishmentName' => $laundry->getEstablishmentName(),
            'address' => $addressData,
            'latitude' => $address?->getLatitude(),
            'longitude' => $address?->getLongitude(),
            'imageUrl' => $this->absoluteUrl($logo?->getLocation()),
            'rating' => $rating,
            'reviewCount' => $reviewCount,
            'isOpenNow' => $isOpenNow,
            'distanceKm' => $distanceKm,
        ];
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Laundry::class => false,
        ];
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
