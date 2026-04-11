<?php

namespace App\DataFixtures;

use App\Entity\PaymentMethod;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PaymentMethodFixtures extends Fixture
{
    public const CARD_NAME = 'Card';
    public const CASH_NAME = 'Cash';
    public const CONTACTLESS_NAME = 'Contactless';

    public function load(ObjectManager $manager): void
    {
        $card = new PaymentMethod();
        $card->setName(self::CARD_NAME);
        $manager->persist($card);

        $cash = new PaymentMethod();
        $cash->setName(self::CASH_NAME);
        $manager->persist($cash);

        $contactless = new PaymentMethod();
        $contactless->setName(self::CONTACTLESS_NAME);
        $manager->persist($contactless);

        $manager->flush();
    }
}
