<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260630071327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add links for laundry';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE laundry ADD website_link VARCHAR(255) DEFAULT NULL, ADD facebook_link VARCHAR(255) DEFAULT NULL, ADD instagram_link VARCHAR(255) DEFAULT NULL, ADD twitter_link VARCHAR(255) DEFAULT NULL, ADD linkedin_link VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE laundry DROP website_link, DROP facebook_link, DROP instagram_link, DROP twitter_link, DROP linkedin_link');
    }
}
