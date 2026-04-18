<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260411193147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE action_nettoyage (id_action INT AUTO_INCREMENT NOT NULL, date_action DATE NOT NULL, lieu VARCHAR(255) NOT NULL, limite_benevoles INT NOT NULL, PRIMARY KEY(id_action)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE activite_ecologique (id_activite INT AUTO_INCREMENT NOT NULL, nom_activite VARCHAR(255) NOT NULL, date_activite DATE NOT NULL, capacite INT NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id_activite)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE faune_marine (id_animal INT AUTO_INCREMENT NOT NULL, espece VARCHAR(255) NOT NULL, etat VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id_animal)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE observation (id_observation INT AUTO_INCREMENT NOT NULL, date_observation DATE NOT NULL, temperature DOUBLE PRECISION DEFAULT NULL, meteo VARCHAR(255) DEFAULT NULL, id_animal INT NOT NULL, PRIMARY KEY(id_observation)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation (id_reservation INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, date_reservation DATE NOT NULL, email VARCHAR(255) NOT NULL, nombre_personnes INT NOT NULL, id_activite INT DEFAULT NULL, INDEX IDX_42C84955E8AEB980 (id_activite), PRIMARY KEY(id_reservation)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE survzone (idSurv INT AUTO_INCREMENT NOT NULL, dateSurv DATE NOT NULL, observation LONGTEXT DEFAULT NULL, idZone INT NOT NULL, PRIMARY KEY(idSurv)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id_utilisateur INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, mot_de_passe VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, role VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, face_encoding LONGBLOB DEFAULT NULL, face_image VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, PRIMARY KEY(id_utilisateur)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE volontaire (id_volontaire INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, contact VARCHAR(20) NOT NULL, id_action INT NOT NULL, INDEX IDX_BB5C9C6B61FB397F (id_action), PRIMARY KEY(id_volontaire)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE zonep (idZone INT AUTO_INCREMENT NOT NULL, nomZone VARCHAR(255) NOT NULL, categorieZone VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(idZone)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955E8AEB980 FOREIGN KEY (id_activite) REFERENCES activite_ecologique (id_activite)');
        $this->addSql('ALTER TABLE volontaire ADD CONSTRAINT FK_BB5C9C6B61FB397F FOREIGN KEY (id_action) REFERENCES action_nettoyage (id_action) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955E8AEB980');
        $this->addSql('ALTER TABLE volontaire DROP FOREIGN KEY FK_BB5C9C6B61FB397F');
        $this->addSql('DROP TABLE action_nettoyage');
        $this->addSql('DROP TABLE activite_ecologique');
        $this->addSql('DROP TABLE faune_marine');
        $this->addSql('DROP TABLE observation');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE survzone');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE volontaire');
        $this->addSql('DROP TABLE zonep');
    }
}
