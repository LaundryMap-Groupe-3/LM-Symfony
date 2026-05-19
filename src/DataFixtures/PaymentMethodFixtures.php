<?php

namespace App\DataFixtures;

use App\Entity\PaymentMethod;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PaymentMethodFixtures extends Fixture
{
    public const CARD_NAME = 'Card';
    public const CONTACTLESS_NAME = 'Contactless';
    public const COINS_NAME = 'Coins';
    public const BILLS_NAME = 'Bills';
    public const FIDELITY_NAME = 'Fidelity';

    public function load(ObjectManager $manager): void
    {
        $card = new PaymentMethod();
        $card->setName(self::CARD_NAME);
        $manager->persist($card);

        $contactless = new PaymentMethod();
        $contactless->setName(self::CONTACTLESS_NAME);
        $manager->persist($contactless);

        $coins = new PaymentMethod();
        $coins->setName(self::COINS_NAME);
        $manager->persist($coins);

        $bills = new PaymentMethod();
        $bills->setName(self::BILLS_NAME);
        $manager->persist($bills);

        $fidelity = new PaymentMethod();
        $fidelity->setName(self::FIDELITY_NAME);
        $manager->persist($fidelity);

        $manager->flush();
    }
}
