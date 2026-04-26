<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260418105133 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Ajouter FK survzone -> zonep
    $this->addSql('ALTER TABLE survzone ADD CONSTRAINT FK_487A1028D3169E99 FOREIGN KEY (idZone) REFERENCES zonep (idZone)');
    
    // Modifier les colonnes de zonep
    $this->addSql('ALTER TABLE zonep CHANGE nomZone nomZone VARCHAR(255) NOT NULL, CHANGE categorieZone categorieZone VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
       
        $this->addSql('ALTER TABLE survzone DROP FOREIGN KEY FK_487A1028D3169E99');
        $this->addSql('ALTER TABLE zonep CHANGE nomZone nomZone VARCHAR(100) NOT NULL, CHANGE categorieZone categorieZone VARCHAR(100) NOT NULL, CHANGE status status VARCHAR(50) NOT NULL');
}
}
