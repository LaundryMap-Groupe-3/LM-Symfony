<?php

namespace App\DataFixtures;

use App\Entity\OffensiveWord;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OffensiveWordFixtures extends Fixture
{
    private const LABELS = [
        'idiot', 'imbecile', 'stupide', 'naze',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::LABELS as $label) {
            $word = new OffensiveWord();
            $word->setLabel($label);
            $manager->persist($word);
        }

        $manager->flush();
    }
}
