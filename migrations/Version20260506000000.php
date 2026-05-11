<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_ban table for managing user bans';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_ban (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            admin_id INT NOT NULL,
            is_permanent TINYINT(1) DEFAULT 0 NOT NULL,
            started_at DATETIME NOT NULL,
            ended_at DATETIME DEFAULT NULL,
            reason LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            FOREIGN KEY(user_id) REFERENCES user(id) ON DELETE CASCADE,
            FOREIGN KEY(admin_id) REFERENCES admin(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_ban');
    }
}
