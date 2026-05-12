<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make reservation->activite foreign key nullable with ON DELETE SET NULL to preserve reservations when activity is deleted.';
    }

    public function up(Schema $schema): void
    {
        $platformClass = strtolower(get_class($this->connection->getDatabasePlatform()));
        $this->abortIf(!str_contains($platformClass, 'mysql') && !str_contains($platformClass, 'maria'), 'Migration can only be executed safely on mysql/mariadb.');

        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY `fk_activite`');

        $this->addSql('ALTER TABLE reservation CHANGE id_activite id_activite INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_activite FOREIGN KEY (id_activite) REFERENCES activite_ecologique (id_activite) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $platformClass = strtolower($this->connection->getDatabasePlatform()::class);
        $this->abortIf(!str_contains($platformClass, 'mysql') && !str_contains($platformClass, 'maria'), 'Migration can only be executed safely on mysql/mariadb.');

        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY `FK_42C84955E8AEB980`');

        $this->addSql('DELETE FROM reservation WHERE id_activite IS NULL');
        $this->addSql('ALTER TABLE reservation CHANGE id_activite id_activite INT NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_activite FOREIGN KEY (id_activite) REFERENCES activite_ecologique (id_activite) ON UPDATE CASCADE ON DELETE CASCADE');
    }
}
