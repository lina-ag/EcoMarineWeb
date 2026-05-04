<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426170714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE survzone ADD CONSTRAINT FK_487A1028D3169E99 FOREIGN KEY (idZone) REFERENCES zonep (idZone)');
        $this->addSql('CREATE INDEX IDX_487A1028D3169E99 ON survzone (idZone)');
        $this->addSql('ALTER TABLE volontaire ADD id_utilisateur INT DEFAULT NULL');
        $this->addSql('ALTER TABLE volontaire ADD CONSTRAINT FK_BB5C9C6B50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_BB5C9C6B50EAE44 ON volontaire (id_utilisateur)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE survzone DROP FOREIGN KEY FK_487A1028D3169E99');
        $this->addSql('DROP INDEX IDX_487A1028D3169E99 ON survzone');
        $this->addSql('ALTER TABLE volontaire DROP FOREIGN KEY FK_BB5C9C6B50EAE44');
        $this->addSql('DROP INDEX IDX_BB5C9C6B50EAE44 ON volontaire');
        $this->addSql('ALTER TABLE volontaire DROP id_utilisateur');
    }
}
