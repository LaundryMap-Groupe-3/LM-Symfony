<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification token table and emailVerifiedAt column to user';
    }

    public function up(Schema $schema): void
    {
        // Add emailVerifiedAt column to user table
        $this->addSql('ALTER TABLE user ADD COLUMN email_verified_at DATETIME NULL');

        // Create email_verification_token table
        $this->addSql('
            CREATE TABLE email_verification_token (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL UNIQUE,
                created_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                is_used TINYINT(1) NOT NULL DEFAULT 0,
                used_at DATETIME NULL,
                PRIMARY KEY (id),
                CONSTRAINT FK_EMAIL_TOKEN_USER FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        // Drop email_verification_token table
        $this->addSql('DROP TABLE email_verification_token');

        // Remove emailVerifiedAt column from user table
        $this->addSql('ALTER TABLE user DROP COLUMN email_verified_at');
    }
}
