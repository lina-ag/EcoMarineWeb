<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426211548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chat_message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, id_observation INT NOT NULL, id_utilisateur INT NOT NULL, INDEX IDX_FAB3FC16DD641617 (id_observation), INDEX IDX_FAB3FC1650EAE44 (id_utilisateur), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE role (id_role INT AUTO_INCREMENT NOT NULL, nom_role VARCHAR(50) NOT NULL, description VARCHAR(255) DEFAULT NULL, niveau INT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_57698A6AA5B94004 (nom_role), PRIMARY KEY(id_role)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16DD641617 FOREIGN KEY (id_observation) REFERENCES observation (id_observation) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC1650EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B4196C2B1A2A1B1 ON faune_marine (espece)');
        $this->addSql('ALTER TABLE observation ADD CONSTRAINT FK_C576DBE04C9C96F2 FOREIGN KEY (id_animal) REFERENCES faune_marine (id_animal) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_C576DBE04C9C96F2 ON observation (id_animal)');
        $this->addSql('ALTER TABLE prediction_echouage ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE survzone DROP FOREIGN KEY FK_487A1028D3169E99');
        $this->addSql('ALTER TABLE survzone CHANGE observation observation LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE survzone ADD CONSTRAINT FK_487A1028D3169E99 FOREIGN KEY (idZone) REFERENCES zonep (idZone) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur ADD id_role INT DEFAULT NULL, DROP role, CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE prenom prenom VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE mot_de_passe mot_de_passe VARCHAR(255) NOT NULL, CHANGE telephone telephone VARCHAR(20) NOT NULL, CHANGE date_naissance date_naissance DATE NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3DC499668 FOREIGN KEY (id_role) REFERENCES role (id_role)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
        $this->addSql('CREATE INDEX IDX_1D1C63B3DC499668 ON utilisateur (id_role)');
        $this->addSql('ALTER TABLE zonep CHANGE nomZone nomZone VARCHAR(100) NOT NULL, CHANGE categorieZone categorieZone VARCHAR(80) NOT NULL, CHANGE status status VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16DD641617');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC1650EAE44');
        $this->addSql('DROP TABLE chat_message');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP INDEX UNIQ_B4196C2B1A2A1B1 ON faune_marine');
        $this->addSql('ALTER TABLE observation DROP FOREIGN KEY FK_C576DBE04C9C96F2');
        $this->addSql('DROP INDEX IDX_C576DBE04C9C96F2 ON observation');
        $this->addSql('ALTER TABLE prediction_echouage DROP latitude, DROP longitude');
        $this->addSql('ALTER TABLE survzone DROP FOREIGN KEY FK_487A1028D3169E99');
        $this->addSql('ALTER TABLE survzone CHANGE observation observation LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE survzone ADD CONSTRAINT FK_487A1028D3169E99 FOREIGN KEY (idZone) REFERENCES zonep (idZone)');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3DC499668');
        $this->addSql('DROP INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur');
        $this->addSql('DROP INDEX IDX_1D1C63B3DC499668 ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur ADD role VARCHAR(255) DEFAULT NULL, DROP id_role, CHANGE nom nom VARCHAR(255) DEFAULT NULL, CHANGE prenom prenom VARCHAR(255) DEFAULT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE mot_de_passe mot_de_passe VARCHAR(255) DEFAULT NULL, CHANGE telephone telephone VARCHAR(255) DEFAULT NULL, CHANGE date_naissance date_naissance DATE DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE zonep CHANGE nomZone nomZone VARCHAR(255) NOT NULL, CHANGE categorieZone categorieZone VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL');
    }
}
