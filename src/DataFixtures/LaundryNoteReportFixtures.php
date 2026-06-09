<?php

namespace App\DataFixtures;

use App\Entity\LaundryNote;
use App\Entity\LaundryNoteReport;
use App\Entity\User;
use App\Enum\LaundryNoteReportReasonEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

class LaundryNoteReportFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $reporter1 = $this->findOneOrFail($manager, User::class, ['email' => 'sophie.bernard@example.com'], 'sophie.bernard@example.com');
        $reporter2 = $this->findOneOrFail($manager, User::class, ['email' => 'luc.petit@example.com'], 'luc.petit@example.com');

        $note1 = $this->findOneOrFail($manager, LaundryNote::class, [
            'comment' => 'Tres propre, bon rapport qualite-prix.',
        ], 'LaundryNote "Tres propre, bon rapport qualite-prix."');

        $note2 = $this->findOneOrFail($manager, LaundryNote::class, [
            'comment' => 'Une machine etait hors service lors de mon passage.',
        ], 'LaundryNote "Une machine etait hors service lors de mon passage."');

        $report1 = new LaundryNoteReport();
        $report1->setLaundryNote($note1);
        $report1->setUser($reporter1);
        $report1->setReason(LaundryNoteReportReasonEnum::SPAM);
        $report1->setComment('Cet avis ressemble a une publicite deguisee.');
        $report1->setCreatedAt(new \DateTime('2026-04-07 09:00:00'));
        $manager->persist($report1);

        $report2 = new LaundryNoteReport();
        $report2->setLaundryNote($note1);
        $report2->setUser($reporter2);
        $report2->setReason(LaundryNoteReportReasonEnum::SPAM);
        $report2->setComment(null);
        $report2->setCreatedAt(new \DateTime('2026-04-10 11:30:00'));
        $manager->persist($report2);

        $report3 = new LaundryNoteReport();
        $report3->setLaundryNote($note2);
        $report3->setUser($reporter2);
        $report3->setReason(LaundryNoteReportReasonEnum::INSULTING);
        $report3->setComment(null);
        $report3->setCreatedAt(new \DateTime('2026-06-01 19:20:00'));
        $manager->persist($report3);

        $manager->flush();
    }

    private function findOneOrFail(ObjectManager $manager, string $entityClass, array $criteria, string $label): object
    {
        $entity = $manager->getRepository($entityClass)->findOneBy($criteria);

        if ($entity === null) {
            throw new RuntimeException(sprintf('%s not found for LaundryNoteReportFixtures.', $label));
        }

        return $entity;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            LaundryNoteFixtures::class,
        ];
    }
}
