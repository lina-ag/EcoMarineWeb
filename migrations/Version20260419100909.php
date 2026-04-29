<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260419100909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Add missing columns and constraints to sync schema
        $this->addSql('ALTER TABLE utilisateur ADD face_encoding LONGBLOB DEFAULT NULL, ADD face_image VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX email ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
    }

    public function down(Schema $schema): void
    {
        // Revert the schema changes
        $this->addSql('ALTER TABLE utilisateur DROP face_encoding, DROP face_image, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_1d1c63b3e7927c74 ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX email ON utilisateur (email)');
    }
}
