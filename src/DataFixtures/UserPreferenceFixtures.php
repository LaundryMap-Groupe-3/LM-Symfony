<?php

namespace App\DataFixtures;

use App\Entity\UserPreference;
use App\Entity\User;
use App\Entity\Language;
use App\Enum\ThemeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserPreferenceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $userRepository = $manager->getRepository(User::class);
        $languageRepository = $manager->getRepository(Language::class);
        
        // Get all users and the French language
        $users = $userRepository->findAll();
        $frenchLanguage = $languageRepository->findOneBy(['code' => 'fr']);
        
        if (!$frenchLanguage) {
            // Create French language if not exists
            $frenchLanguage = new Language();
            $frenchLanguage->setName('Français');
            $frenchLanguage->setCode('fr');
            $manager->persist($frenchLanguage);
            $manager->flush();
        }
        
        // Create preferences for each user
        foreach ($users as $user) {
            $existingPreference = $user->getUserPreference();
            
            // Skip if preferences already exist
            if ($existingPreference) {
                continue;
            }
            
            $preference = new UserPreference();
            $preference->setUser($user);
            $preference->setLanguage($frenchLanguage);
            $preference->setTheme(ThemeEnum::LIGHT);
            $preference->setNotifications(true);
            $manager->persist($preference);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            LanguageFixtures::class,
        ];
    }
}
