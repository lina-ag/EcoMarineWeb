<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260419115554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE observation DROP FOREIGN KEY FK_C576DBE04C9C96F2');
        $this->addSql('DROP INDEX fk_c576dbe04c9c96f2 ON observation');
        $this->addSql('CREATE INDEX IDX_C576DBE04C9C96F2 ON observation (id_animal)');
        $this->addSql('ALTER TABLE observation ADD CONSTRAINT FK_C576DBE04C9C96F2 FOREIGN KEY (id_animal) REFERENCES faune_marine (id_animal) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE prediction_echouage ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL, CHANGE zone zone VARCHAR(255) NOT NULL, CHANGE espece_concernee espece_concernee VARCHAR(255) DEFAULT NULL, CHANGE conditions_meteo conditions_meteo VARCHAR(255) DEFAULT NULL, CHANGE recommandations recommandations LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD face_encoding LONGBLOB DEFAULT NULL, ADD face_image VARCHAR(255) DEFAULT NULL, CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE prenom prenom VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE mot_de_passe mot_de_passe VARCHAR(255) NOT NULL, CHANGE telephone telephone VARCHAR(20) NOT NULL, CHANGE role role VARCHAR(50) NOT NULL, CHANGE date_naissance date_naissance DATE NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX email ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE observation DROP FOREIGN KEY FK_C576DBE04C9C96F2');
        $this->addSql('DROP INDEX idx_c576dbe04c9c96f2 ON observation');
        $this->addSql('CREATE INDEX FK_C576DBE04C9C96F2 ON observation (id_animal)');
        $this->addSql('ALTER TABLE observation ADD CONSTRAINT FK_C576DBE04C9C96F2 FOREIGN KEY (id_animal) REFERENCES faune_marine (id_animal) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE prediction_echouage DROP latitude, DROP longitude, CHANGE zone zone VARCHAR(50) NOT NULL, CHANGE espece_concernee espece_concernee VARCHAR(100) DEFAULT NULL, CHANGE conditions_meteo conditions_meteo VARCHAR(200) DEFAULT NULL, CHANGE recommandations recommandations TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur DROP face_encoding, DROP face_image, CHANGE nom nom VARCHAR(100) DEFAULT NULL, CHANGE prenom prenom VARCHAR(100) DEFAULT NULL, CHANGE email email VARCHAR(150) DEFAULT NULL, CHANGE mot_de_passe mot_de_passe VARCHAR(255) DEFAULT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE role role VARCHAR(50) DEFAULT NULL, CHANGE date_naissance date_naissance DATE DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_1d1c63b3e7927c74 ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX email ON utilisateur (email)');
    }
}
