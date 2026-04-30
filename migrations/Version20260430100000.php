<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create offensive_word table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE offensive_word (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE offensive_word');
    }
}