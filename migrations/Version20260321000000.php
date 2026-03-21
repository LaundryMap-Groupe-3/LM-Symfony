<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset token table for password reset functionality';
    }

    public function up(Schema $schema): void
    {
        // Create password_reset_token table
        $this->addSql('
            CREATE TABLE password_reset_token (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL UNIQUE,
                created_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                is_used TINYINT(1) NOT NULL DEFAULT 0,
                used_at DATETIME NULL,
                PRIMARY KEY (id),
                CONSTRAINT FK_PASSWORD_RESET_TOKEN_USER FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');

        // Create index for faster token lookups
        $this->addSql('CREATE INDEX idx_password_reset_token_token ON password_reset_token(token)');
        $this->addSql('CREATE INDEX idx_password_reset_token_user_id ON password_reset_token(user_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop indices
        $this->addSql('DROP INDEX idx_password_reset_token_token ON password_reset_token');
        $this->addSql('DROP INDEX idx_password_reset_token_user_id ON password_reset_token');

        // Drop password_reset_token table
        $this->addSql('DROP TABLE password_reset_token');
    }
}
