<?php

namespace App\Repository;

use App\Entity\OffensiveWord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OffensiveWord>
 */
class OffensiveWordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OffensiveWord::class);
    }

    public function findOffensiveLabelInText(string $text): ?string
    {
        $normalizedText = mb_strtolower($text);

        foreach ($this->findBy([], ['label' => 'ASC']) as $offensiveWord) {
            $label = trim((string) $offensiveWord->getLabel());
            if ($label === '') {
                continue;
            }

            $pattern = '/(^|[^\p{L}\p{N}_])' . preg_quote(mb_strtolower($label), '/') . '($|[^\p{L}\p{N}_])/iu';
            if (preg_match($pattern, $normalizedText) === 1) {
                return $label;
            }
        }

        return null;
    }
}
