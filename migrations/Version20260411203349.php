<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260411203349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY fk_activite');
        $this->addSql('DROP INDEX fk_activite ON reservation');
        $this->addSql('CREATE INDEX IDX_42C84955E8AEB980 ON reservation (id_activite)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_activite FOREIGN KEY (id_activite) REFERENCES activite_ecologique (id_activite)');
        $this->addSql('ALTER TABLE survzone DROP FOREIGN KEY fk_zone_surv');
        $this->addSql('DROP INDEX fk_zone_surv ON survzone');
        $this->addSql('ALTER TABLE survzone CHANGE observation observation LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX email ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur CHANGE nom nom VARCHAR(255) DEFAULT NULL, CHANGE prenom prenom VARCHAR(255) DEFAULT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE telephone telephone VARCHAR(255) DEFAULT NULL, CHANGE role role VARCHAR(255) DEFAULT NULL, CHANGE face_encoding face_encoding LONGBLOB DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE volontaire DROP FOREIGN KEY volontaire_ibfk_1');
        $this->addSql('DROP INDEX id_action ON volontaire');
        $this->addSql('ALTER TABLE volontaire CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE contact contact VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE zonep CHANGE nomZone nomZone VARCHAR(255) NOT NULL, CHANGE categorieZone categorieZone VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955E8AEB980');
        $this->addSql('DROP INDEX idx_42c84955e8aeb980 ON reservation');
        $this->addSql('CREATE INDEX fk_activite ON reservation (id_activite)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955E8AEB980 FOREIGN KEY (id_activite) REFERENCES activite_ecologique (id_activite)');
        $this->addSql('ALTER TABLE survzone CHANGE observation observation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE survzone ADD CONSTRAINT fk_zone_surv FOREIGN KEY (idZone) REFERENCES zonep (idZone) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX fk_zone_surv ON survzone (idZone)');
        $this->addSql('ALTER TABLE utilisateur CHANGE nom nom VARCHAR(100) DEFAULT NULL, CHANGE prenom prenom VARCHAR(100) DEFAULT NULL, CHANGE email email VARCHAR(150) DEFAULT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE role role VARCHAR(50) DEFAULT NULL, CHANGE face_encoding face_encoding BLOB DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX email ON utilisateur (email)');
        $this->addSql('ALTER TABLE volontaire CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE contact contact VARCHAR(30) DEFAULT NULL');
        $this->addSql('ALTER TABLE volontaire ADD CONSTRAINT volontaire_ibfk_1 FOREIGN KEY (id_action) REFERENCES action_nettoyage (id_action) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE INDEX id_action ON volontaire (id_action)');
        $this->addSql('ALTER TABLE zonep CHANGE nomZone nomZone VARCHAR(100) NOT NULL, CHANGE categorieZone categorieZone VARCHAR(100) NOT NULL, CHANGE status status VARCHAR(50) NOT NULL');
    }
}
