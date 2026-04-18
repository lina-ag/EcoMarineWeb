<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413133157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE action_nettoyage CHANGE date_action date_action DATE NOT NULL, CHANGE lieu lieu VARCHAR(255) NOT NULL, CHANGE limite_benevoles limite_benevoles INT NOT NULL');
        $this->addSql('ALTER TABLE activite_ecologique CHANGE nom_activite nom_activite VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE biodiversite CHANGE espece espece VARCHAR(255) NOT NULL, CHANGE zone zone VARCHAR(255) NOT NULL, CHANGE date date VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE dechet CHANGE type type VARCHAR(255) NOT NULL, CHANGE zone zone VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX id_mission ON detection_drone');
        $this->addSql('ALTER TABLE detection_drone CHANGE espece espece VARCHAR(255) NOT NULL, CHANGE nombre_individus nombre_individus INT NOT NULL, CHANGE comportement comportement VARCHAR(255) DEFAULT NULL, CHANGE confiance_ia confiance_ia VARCHAR(255) DEFAULT NULL, CHANGE timestamp timestamp VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE evenement CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE date date VARCHAR(255) NOT NULL, CHANGE lieu lieu VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE faune_marine CHANGE espece espece VARCHAR(255) NOT NULL, CHANGE etat etat VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B4196C2B1A2A1B1 ON faune_marine (espece)');
        $this->addSql('ALTER TABLE mission_drone CHANGE zone_survolee zone_survolee VARCHAR(255) NOT NULL, CHANGE conditions_vol conditions_vol VARCHAR(255) DEFAULT NULL, CHANGE observations observations LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE observation CHANGE temperature temperature DOUBLE PRECISION DEFAULT NULL, CHANGE meteo meteo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE observation ADD CONSTRAINT FK_C576DBE04C9C96F2 FOREIGN KEY (id_animal) REFERENCES faune_marine (id_animal) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE observation RENAME INDEX fk_animal TO IDX_C576DBE04C9C96F2');
        $this->addSql('ALTER TABLE prediction_echouage CHANGE zone zone VARCHAR(255) NOT NULL, CHANGE espece_concernee espece_concernee VARCHAR(255) DEFAULT NULL, CHANGE conditions_meteo conditions_meteo VARCHAR(255) DEFAULT NULL, CHANGE recommandations recommandations LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY fk_activite');
        $this->addSql('ALTER TABLE reservation CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE id_activite id_activite INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955E8AEB980 FOREIGN KEY (id_activite) REFERENCES activite_ecologique (id_activite)');
        $this->addSql('ALTER TABLE reservation RENAME INDEX fk_activite TO IDX_42C84955E8AEB980');
        $this->addSql('ALTER TABLE survzone CHANGE observation observation LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE survzone ADD CONSTRAINT FK_487A1028D3169E99 FOREIGN KEY (idZone) REFERENCES zonep (idZone)');
        $this->addSql('ALTER TABLE survzone RENAME INDEX fk_zone_surv TO IDX_487A1028D3169E99');
        $this->addSql('ALTER TABLE utilisateur CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE prenom prenom VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE mot_de_passe mot_de_passe VARCHAR(255) NOT NULL, CHANGE telephone telephone VARCHAR(20) NOT NULL, CHANGE role role VARCHAR(50) NOT NULL, CHANGE date_naissance date_naissance DATE NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX email TO UNIQ_1D1C63B3E7927C74');
        $this->addSql('ALTER TABLE volontaire CHANGE contact contact VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE volontaire ADD CONSTRAINT FK_BB5C9C6B61FB397F FOREIGN KEY (id_action) REFERENCES action_nettoyage (id_action) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volontaire RENAME INDEX id_action TO IDX_BB5C9C6B61FB397F');
        $this->addSql('ALTER TABLE zone_plage CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE localisation localisation VARCHAR(255) NOT NULL, CHANGE statut statut VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE zonep CHANGE nomZone nomZone VARCHAR(255) NOT NULL, CHANGE categorieZone categorieZone VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE biodiversite CHANGE espece espece VARCHAR(100) NOT NULL, CHANGE zone zone VARCHAR(100) NOT NULL, CHANGE date date VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE action_nettoyage CHANGE date_action date_action VARCHAR(10) NOT NULL, CHANGE lieu lieu VARCHAR(150) NOT NULL, CHANGE limite_benevoles limite_benevoles INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE zonep CHANGE nomZone nomZone VARCHAR(100) NOT NULL, CHANGE categorieZone categorieZone VARCHAR(100) NOT NULL, CHANGE status status VARCHAR(50) NOT NULL');
        $this->addSql('DROP INDEX UNIQ_B4196C2B1A2A1B1 ON faune_marine');
        $this->addSql('ALTER TABLE faune_marine CHANGE espece espece VARCHAR(100) NOT NULL, CHANGE etat etat VARCHAR(50) NOT NULL, CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE zone_plage CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE localisation localisation VARCHAR(100) NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE volontaire DROP FOREIGN KEY FK_BB5C9C6B61FB397F');
        $this->addSql('ALTER TABLE volontaire CHANGE contact contact VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE volontaire RENAME INDEX idx_bb5c9c6b61fb397f TO id_action');
        $this->addSql('ALTER TABLE observation DROP FOREIGN KEY FK_C576DBE04C9C96F2');
        $this->addSql('ALTER TABLE observation CHANGE temperature temperature NUMERIC(5, 2) DEFAULT NULL, CHANGE meteo meteo VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE observation RENAME INDEX idx_c576dbe04c9c96f2 TO fk_animal');
        $this->addSql('ALTER TABLE activite_ecologique CHANGE nom_activite nom_activite VARCHAR(20) NOT NULL, CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE detection_drone CHANGE espece espece VARCHAR(100) NOT NULL, CHANGE nombre_individus nombre_individus INT DEFAULT 1 NOT NULL, CHANGE comportement comportement VARCHAR(100) DEFAULT NULL, CHANGE confiance_ia confiance_ia VARCHAR(20) DEFAULT \'Moyenne\', CHANGE timestamp timestamp VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE INDEX id_mission ON detection_drone (id_mission)');
        $this->addSql('ALTER TABLE survzone DROP FOREIGN KEY FK_487A1028D3169E99');
        $this->addSql('ALTER TABLE survzone CHANGE observation observation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE survzone RENAME INDEX idx_487a1028d3169e99 TO fk_zone_surv');
        $this->addSql('ALTER TABLE prediction_echouage CHANGE zone zone VARCHAR(50) NOT NULL, CHANGE espece_concernee espece_concernee VARCHAR(100) DEFAULT NULL, CHANGE conditions_meteo conditions_meteo VARCHAR(200) DEFAULT NULL, CHANGE recommandations recommandations TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955E8AEB980');
        $this->addSql('ALTER TABLE reservation CHANGE nom nom VARCHAR(20) NOT NULL, CHANGE email email VARCHAR(50) NOT NULL, CHANGE id_activite id_activite INT NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_activite FOREIGN KEY (id_activite) REFERENCES activite_ecologique (id_activite) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation RENAME INDEX idx_42c84955e8aeb980 TO fk_activite');
        $this->addSql('ALTER TABLE utilisateur CHANGE nom nom VARCHAR(100) DEFAULT NULL, CHANGE prenom prenom VARCHAR(100) DEFAULT NULL, CHANGE email email VARCHAR(150) DEFAULT NULL, CHANGE mot_de_passe mot_de_passe VARCHAR(255) DEFAULT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE role role VARCHAR(50) DEFAULT NULL, CHANGE date_naissance date_naissance DATE DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX uniq_1d1c63b3e7927c74 TO email');
        $this->addSql('ALTER TABLE mission_drone CHANGE zone_survolee zone_survolee VARCHAR(100) NOT NULL, CHANGE conditions_vol conditions_vol VARCHAR(50) DEFAULT NULL, CHANGE observations observations TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE dechet CHANGE type type VARCHAR(100) NOT NULL, CHANGE zone zone VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE evenement CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE date date VARCHAR(50) NOT NULL, CHANGE lieu lieu VARCHAR(150) NOT NULL');
    }
}
