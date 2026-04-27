<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260418174735 extends AbstractMigration
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
        $this->addSql('CREATE TABLE biodiversite (id INT AUTO_INCREMENT NOT NULL, espece VARCHAR(255) NOT NULL, zone VARCHAR(255) NOT NULL, nombre INT NOT NULL, date VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dechet (id_dechet INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, quantite DOUBLE PRECISION NOT NULL, zone VARCHAR(255) NOT NULL, PRIMARY KEY(id_dechet)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE detection_drone (id_detection INT AUTO_INCREMENT NOT NULL, id_mission INT NOT NULL, espece VARCHAR(255) NOT NULL, nombre_individus INT NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, comportement VARCHAR(255) DEFAULT NULL, confiance_ia VARCHAR(255) DEFAULT NULL, image_path VARCHAR(255) DEFAULT NULL, timestamp VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id_detection)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, date VARCHAR(255) NOT NULL, lieu VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE faune_marine (id_animal INT AUTO_INCREMENT NOT NULL, espece VARCHAR(255) NOT NULL, etat VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_B4196C2B1A2A1B1 (espece), PRIMARY KEY(id_animal)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mission_drone (id_mission INT AUTO_INCREMENT NOT NULL, date_mission DATE NOT NULL, heure_debut TIME NOT NULL, heure_fin TIME NOT NULL, zone_survolee VARCHAR(255) NOT NULL, distance_parcourue DOUBLE PRECISION DEFAULT NULL, altitude_vol INT DEFAULT NULL, conditions_vol VARCHAR(255) DEFAULT NULL, observations LONGTEXT DEFAULT NULL, PRIMARY KEY(id_mission)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE observation (id_observation INT AUTO_INCREMENT NOT NULL, date_observation DATE NOT NULL, temperature DOUBLE PRECISION DEFAULT NULL, meteo VARCHAR(255) DEFAULT NULL, id_animal INT NOT NULL, INDEX IDX_C576DBE04C9C96F2 (id_animal), PRIMARY KEY(id_observation)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE prediction_echouage (id_prediction INT AUTO_INCREMENT NOT NULL, date_prediction DATE NOT NULL, zone VARCHAR(255) NOT NULL, niveau_risque INT NOT NULL, espece_concernee VARCHAR(255) DEFAULT NULL, temperature_eau DOUBLE PRECISION DEFAULT NULL, conditions_meteo VARCHAR(255) DEFAULT NULL, recommandations LONGTEXT DEFAULT NULL, PRIMARY KEY(id_prediction)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation (id_reservation INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, date_reservation DATE NOT NULL, email VARCHAR(255) NOT NULL, nombre_personnes INT NOT NULL, id_activite INT DEFAULT NULL, INDEX IDX_42C84955E8AEB980 (id_activite), PRIMARY KEY(id_reservation)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE role (id_role INT AUTO_INCREMENT NOT NULL, nom_role VARCHAR(50) NOT NULL, description VARCHAR(255) DEFAULT NULL, niveau INT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_57698A6AA5B94004 (nom_role), PRIMARY KEY(id_role)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE survzone (idSurv INT AUTO_INCREMENT NOT NULL, dateSurv DATE NOT NULL, observation LONGTEXT DEFAULT NULL, idZone INT NOT NULL, INDEX IDX_487A1028D3169E99 (idZone), PRIMARY KEY(idSurv)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id_utilisateur INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, date_naissance DATE NOT NULL, face_encoding LONGBLOB DEFAULT NULL, face_image VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, id_role INT DEFAULT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), INDEX IDX_1D1C63B3DC499668 (id_role), PRIMARY KEY(id_utilisateur)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE volontaire (id_volontaire INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, contact VARCHAR(20) NOT NULL, id_action INT NOT NULL, INDEX IDX_BB5C9C6B61FB397F (id_action), PRIMARY KEY(id_volontaire)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE zone_plage (id_zone INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, localisation VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, PRIMARY KEY(id_zone)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE zonep (idZone INT AUTO_INCREMENT NOT NULL, nomZone VARCHAR(255) NOT NULL, categorieZone VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(idZone)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE observation ADD CONSTRAINT FK_C576DBE04C9C96F2 FOREIGN KEY (id_animal) REFERENCES faune_marine (id_animal) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955E8AEB980 FOREIGN KEY (id_activite) REFERENCES activite_ecologique (id_activite)');
        $this->addSql('ALTER TABLE survzone ADD CONSTRAINT FK_487A1028D3169E99 FOREIGN KEY (idZone) REFERENCES zonep (idZone)');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3DC499668 FOREIGN KEY (id_role) REFERENCES role (id_role)');
        $this->addSql('ALTER TABLE volontaire ADD CONSTRAINT FK_BB5C9C6B61FB397F FOREIGN KEY (id_action) REFERENCES action_nettoyage (id_action) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE observation DROP FOREIGN KEY FK_C576DBE04C9C96F2');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955E8AEB980');
        $this->addSql('ALTER TABLE survzone DROP FOREIGN KEY FK_487A1028D3169E99');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3DC499668');
        $this->addSql('ALTER TABLE volontaire DROP FOREIGN KEY FK_BB5C9C6B61FB397F');
        $this->addSql('DROP TABLE action_nettoyage');
        $this->addSql('DROP TABLE activite_ecologique');
        $this->addSql('DROP TABLE biodiversite');
        $this->addSql('DROP TABLE dechet');
        $this->addSql('DROP TABLE detection_drone');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE faune_marine');
        $this->addSql('DROP TABLE mission_drone');
        $this->addSql('DROP TABLE observation');
        $this->addSql('DROP TABLE prediction_echouage');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE survzone');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE volontaire');
        $this->addSql('DROP TABLE zone_plage');
        $this->addSql('DROP TABLE zonep');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
