<?php

namespace App\DataFixtures;

use App\Entity\Language;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LanguageFixtures extends Fixture
{
    public const FRENCH_REFERENCE = 'language_fr';
    public const ENGLISH_REFERENCE = 'language_en';

    public function load(ObjectManager $manager): void
    {
        $french = new Language();
        $french->setName('Français');
        $french->setCode('fr');
        $manager->persist($french);
        $this->addReference(self::FRENCH_REFERENCE, $french);

        $english = new Language();
        $english->setName('English');
        $english->setCode('en');
        $manager->persist($english);
        $this->addReference(self::ENGLISH_REFERENCE, $english);

        $manager->flush();
    }
}
