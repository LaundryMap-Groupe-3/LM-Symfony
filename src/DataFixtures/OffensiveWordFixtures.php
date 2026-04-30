<?php

namespace App\DataFixtures;

use App\Entity\OffensiveWord;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OffensiveWordFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach (['idiot', 'imbecile', 'imbécile', 'stupide'] as $label) {
            $offensiveWord = new OffensiveWord();
            $offensiveWord->setLabel($label);
            $manager->persist($offensiveWord);
        }

        $manager->flush();
    }
}