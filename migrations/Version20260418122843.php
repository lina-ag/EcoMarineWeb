<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418122843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE survzone CHANGE observation observation LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE survzone DROP FOREIGN KEY FK_survzone_zonep');
        $this->addSql('ALTER TABLE survzone ADD CONSTRAINT FK_487A1028D3169E99 FOREIGN KEY (idZone) REFERENCES zonep (idZone) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE zonep CHANGE nomZone nomZone VARCHAR(100) NOT NULL, CHANGE categorieZone categorieZone VARCHAR(80) NOT NULL, CHANGE status status VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE survzone DROP FOREIGN KEY FK_487A1028D3169E99');
        $this->addSql('ALTER TABLE survzone CHANGE observation observation LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE survzone ADD CONSTRAINT FK_survzone_zonep FOREIGN KEY (idZone) REFERENCES zonep (idZone)');
        $this->addSql('ALTER TABLE zonep CHANGE nomZone nomZone VARCHAR(255) NOT NULL, CHANGE categorieZone categorieZone VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL');
    }
}