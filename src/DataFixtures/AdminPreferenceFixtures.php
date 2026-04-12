<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\AdminPreference;
use App\Entity\Language;
use App\Enum\ThemeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AdminPreferenceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $adminRepository = $manager->getRepository(Admin::class);
        $languageRepository = $manager->getRepository(Language::class);
        
        // Get all admins and the French language
        $admins = $adminRepository->findAll();
        $frenchLanguage = $languageRepository->findOneBy(['code' => 'fr']);
        
        if (!$frenchLanguage) {
            // Create French language if not exists
            $frenchLanguage = new Language();
            $frenchLanguage->setName('Français');
            $frenchLanguage->setCode('fr');
            $manager->persist($frenchLanguage);
            $manager->flush();
        }
        
        // Create preferences for each admin
        foreach ($admins as $admin) {
            $existingPreference = $admin->getAdminPreference();
            
            // Skip if preferences already exist
            if ($existingPreference) {
                continue;
            }
            
            $preference = new AdminPreference();
            $preference->setAdmin($admin);
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
            AdminFixtures::class,
            LanguageFixtures::class,
        ];
    }
}
