<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_login_at to admin';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin ADD last_login_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin DROP last_login_at');
    }
}