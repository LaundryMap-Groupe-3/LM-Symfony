<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260412135152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_preference (id INT AUTO_INCREMENT NOT NULL, theme VARCHAR(255) DEFAULT NULL, notifications TINYINT NOT NULL, admin_id INT NOT NULL, language_id INT NOT NULL, UNIQUE INDEX UNIQ_955716D6642B8210 (admin_id), INDEX IDX_955716D682F1BAF4 (language_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE admin_preference ADD CONSTRAINT FK_955716D6642B8210 FOREIGN KEY (admin_id) REFERENCES admin (id)');
        $this->addSql('ALTER TABLE admin_preference ADD CONSTRAINT FK_955716D682F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admin_preference DROP FOREIGN KEY FK_955716D6642B8210');
        $this->addSql('ALTER TABLE admin_preference DROP FOREIGN KEY FK_955716D682F1BAF4');
        $this->addSql('DROP TABLE admin_preference');
    }
}
