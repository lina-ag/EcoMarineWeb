<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional utilisateur relation to reservation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD id_utilisateur INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_3DAF2E6E50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3DAF2E6E50EAE44 ON reservation (id_utilisateur)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_3DAF2E6E50EAE44 ON reservation');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_3DAF2E6E50EAE44');
        $this->addSql('ALTER TABLE reservation DROP id_utilisateur');
    }
}