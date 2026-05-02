<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426113649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE blocked_country (id INT AUTO_INCREMENT NOT NULL, country_code VARCHAR(2) NOT NULL, country_name VARCHAR(100) NOT NULL, blocked_at DATETIME NOT NULL, reason VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_EAA1A190F026BB7C (country_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('DROP TABLE detection_echouage');
        $this->addSql('ALTER TABLE action_nettoyage ADD limite_benevoles INT NOT NULL, CHANGE date_action date_action DATE NOT NULL, CHANGE lieu lieu VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE activite_ecologique CHANGE nom_activite nom_activite VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE biodiversite CHANGE espece espece VARCHAR(255) NOT NULL, CHANGE zone zone VARCHAR(255) NOT NULL, CHANGE date date VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE dechet CHANGE type type VARCHAR(255) NOT NULL, CHANGE zone zone VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE detection_drone DROP FOREIGN KEY detection_drone_ibfk_1');
        $this->addSql('DROP INDEX id_mission ON detection_drone');
        $this->addSql('ALTER TABLE detection_drone CHANGE espece espece VARCHAR(255) NOT NULL, CHANGE nombre_individus nombre_individus INT NOT NULL, CHANGE latitude latitude DOUBLE PRECISION DEFAULT NULL, CHANGE longitude longitude DOUBLE PRECISION DEFAULT NULL, CHANGE comportement comportement VARCHAR(255) DEFAULT NULL, CHANGE confiance_ia confiance_ia VARCHAR(255) DEFAULT NULL, CHANGE image_path image_path VARCHAR(255) DEFAULT NULL, CHANGE timestamp timestamp VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE evenement CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE date date VARCHAR(255) NOT NULL, CHANGE lieu lieu VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE faune_marine CHANGE espece espece VARCHAR(255) NOT NULL, CHANGE etat etat VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B4196C2B1A2A1B1 ON faune_marine (espece)');
        $this->addSql('ALTER TABLE mission_drone CHANGE zone_survolee zone_survolee VARCHAR(255) NOT NULL, CHANGE distance_parcourue distance_parcourue DOUBLE PRECISION DEFAULT NULL, CHANGE conditions_vol conditions_vol VARCHAR(255) DEFAULT NULL, CHANGE observations observations LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE observation CHANGE temperature temperature DOUBLE PRECISION DEFAULT NULL, CHANGE meteo meteo VARCHAR(255) DEFAULT NULL, CHANGE id_animal id_animal INT NOT NULL');
        $this->addSql('ALTER TABLE observation RENAME INDEX id_animal TO IDX_C576DBE04C9C96F2');
        $this->addSql('ALTER TABLE prediction_echouage CHANGE zone zone VARCHAR(255) NOT NULL, CHANGE espece_concernee espece_concernee VARCHAR(255) DEFAULT NULL, CHANGE temperature_eau temperature_eau DOUBLE PRECISION DEFAULT NULL, CHANGE conditions_meteo conditions_meteo VARCHAR(255) DEFAULT NULL, CHANGE recommandations recommandations LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE id_activite id_activite INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation RENAME INDEX fk_activite TO IDX_42C84955E8AEB980');
        $this->addSql('ALTER TABLE role CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE niveau niveau INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE role RENAME INDEX nom_role TO UNIQ_57698A6AA5B94004');
        $this->addSql('ALTER TABLE survzone CHANGE observation observation LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE survzone RENAME INDEX fk_zone_surv TO IDX_487A1028D3169E99');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY fk_utilisateur_role');
        $this->addSql('ALTER TABLE utilisateur CHANGE face_image face_image VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3DC499668 FOREIGN KEY (id_role) REFERENCES role (id_role)');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX email TO UNIQ_1D1C63B3E7927C74');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX fk_utilisateur_role TO IDX_1D1C63B3DC499668');
        $this->addSql('ALTER TABLE volontaire DROP FOREIGN KEY fk_volontaire_action');
        $this->addSql('ALTER TABLE volontaire CHANGE contact contact VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE volontaire ADD CONSTRAINT FK_BB5C9C6B61FB397F FOREIGN KEY (id_action) REFERENCES action_nettoyage (id_action) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volontaire RENAME INDEX fk_volontaire_action TO IDX_BB5C9C6B61FB397F');
        $this->addSql('ALTER TABLE zone_plage CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE localisation localisation VARCHAR(255) NOT NULL, CHANGE statut statut VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE zonep CHANGE categorieZone categorieZone VARCHAR(80) NOT NULL, CHANGE status status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE detection_echouage (id_detection INT AUTO_INCREMENT NOT NULL, date_detection DATETIME NOT NULL, zone VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, espece VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, nombre_individus INT DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT \'NULL\', longitude DOUBLE PRECISION DEFAULT \'NULL\', description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(id_detection)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE blocked_country');
        $this->addSql('ALTER TABLE action_nettoyage DROP limite_benevoles, CHANGE date_action date_action VARCHAR(50) NOT NULL, CHANGE lieu lieu VARCHAR(150) NOT NULL');
        $this->addSql('ALTER TABLE activite_ecologique CHANGE nom_activite nom_activite VARCHAR(20) NOT NULL, CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE biodiversite CHANGE espece espece VARCHAR(100) NOT NULL, CHANGE zone zone VARCHAR(100) NOT NULL, CHANGE date date VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE dechet CHANGE type type VARCHAR(100) NOT NULL, CHANGE zone zone VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE detection_drone CHANGE espece espece VARCHAR(100) NOT NULL, CHANGE nombre_individus nombre_individus INT DEFAULT 1 NOT NULL, CHANGE latitude latitude DOUBLE PRECISION DEFAULT \'NULL\', CHANGE longitude longitude DOUBLE PRECISION DEFAULT \'NULL\', CHANGE comportement comportement VARCHAR(100) DEFAULT \'NULL\', CHANGE confiance_ia confiance_ia VARCHAR(20) DEFAULT \'\'\'Moyenne\'\'\', CHANGE image_path image_path VARCHAR(255) DEFAULT \'NULL\', CHANGE timestamp timestamp VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE detection_drone ADD CONSTRAINT detection_drone_ibfk_1 FOREIGN KEY (id_mission) REFERENCES mission_drone (id_mission) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX id_mission ON detection_drone (id_mission)');
        $this->addSql('ALTER TABLE evenement CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE date date VARCHAR(50) NOT NULL, CHANGE lieu lieu VARCHAR(150) NOT NULL, CHANGE description description VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('DROP INDEX UNIQ_B4196C2B1A2A1B1 ON faune_marine');
        $this->addSql('ALTER TABLE faune_marine CHANGE espece espece VARCHAR(100) NOT NULL, CHANGE etat etat VARCHAR(50) DEFAULT \'NULL\', CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE mission_drone CHANGE zone_survolee zone_survolee VARCHAR(100) NOT NULL, CHANGE distance_parcourue distance_parcourue DOUBLE PRECISION DEFAULT \'NULL\', CHANGE conditions_vol conditions_vol VARCHAR(50) DEFAULT \'NULL\', CHANGE observations observations TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE observation CHANGE temperature temperature DOUBLE PRECISION DEFAULT \'NULL\', CHANGE meteo meteo VARCHAR(50) DEFAULT \'NULL\', CHANGE id_animal id_animal INT DEFAULT NULL');
        $this->addSql('ALTER TABLE observation RENAME INDEX idx_c576dbe04c9c96f2 TO id_animal');
        $this->addSql('ALTER TABLE prediction_echouage CHANGE zone zone VARCHAR(50) NOT NULL, CHANGE espece_concernee espece_concernee VARCHAR(100) DEFAULT \'NULL\', CHANGE temperature_eau temperature_eau DOUBLE PRECISION DEFAULT \'NULL\', CHANGE conditions_meteo conditions_meteo VARCHAR(200) DEFAULT \'NULL\', CHANGE recommandations recommandations TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation CHANGE nom nom VARCHAR(20) NOT NULL, CHANGE email email VARCHAR(20) NOT NULL, CHANGE id_activite id_activite INT NOT NULL');
        $this->addSql('ALTER TABLE reservation RENAME INDEX idx_42c84955e8aeb980 TO fk_activite');
        $this->addSql('ALTER TABLE role CHANGE description description VARCHAR(255) DEFAULT \'NULL\', CHANGE niveau niveau INT DEFAULT 1, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE role RENAME INDEX uniq_57698a6aa5b94004 TO nom_role');
        $this->addSql('ALTER TABLE survzone CHANGE observation observation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE survzone RENAME INDEX idx_487a1028d3169e99 TO fk_zone_surv');
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3DC499668');
        $this->addSql('ALTER TABLE utilisateur CHANGE face_image face_image VARCHAR(255) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT fk_utilisateur_role FOREIGN KEY (id_role) REFERENCES role (id_role) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX uniq_1d1c63b3e7927c74 TO email');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX idx_1d1c63b3dc499668 TO fk_utilisateur_role');
        $this->addSql('ALTER TABLE volontaire DROP FOREIGN KEY FK_BB5C9C6B61FB397F');
        $this->addSql('ALTER TABLE volontaire CHANGE contact contact VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE volontaire ADD CONSTRAINT fk_volontaire_action FOREIGN KEY (id_action) REFERENCES action_nettoyage (id_action) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volontaire RENAME INDEX idx_bb5c9c6b61fb397f TO fk_volontaire_action');
        $this->addSql('ALTER TABLE zonep CHANGE categorieZone categorieZone VARCHAR(100) NOT NULL, CHANGE status status VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE zone_plage CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE localisation localisation VARCHAR(100) NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL');
    }
}
