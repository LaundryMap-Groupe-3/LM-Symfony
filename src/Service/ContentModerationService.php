<?php

namespace App\Service;

use App\Repository\OffensiveWordRepository;

class ContentModerationService
{
    private ?array $blockedWords = null;

    public function __construct(
        private readonly OffensiveWordRepository $offensiveWordRepository,
    ) {
    }

    public function containsOffensiveContent(?string $text): bool
    {
        if ($text === null || $text === '') {
            return false;
        }

        $normalized = $this->normalize($text);

        foreach ($this->getBlockedWords() as $word) {
            if ($word !== '' && str_contains($normalized, $word)) {
                return true;
            }
        }

        return false;
    }

    private function getBlockedWords(): array
    {
        if ($this->blockedWords === null) {
            $this->blockedWords = array_map(
                fn(string $label) => $this->normalize($label),
                $this->offensiveWordRepository->findAllLabels()
            );
        }

        return $this->blockedWords;
    }

    private function normalize(string $text): string
    {
        $lower = mb_strtolower($text);
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lower);

        return $transliterated !== false ? $transliterated : $lower;
    }
}
