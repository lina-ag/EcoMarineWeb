<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502195500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_blocked and blocked_at columns to utilisateur table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD is_blocked TINYINT(1) NOT NULL DEFAULT 0, ADD blocked_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP is_blocked, DROP blocked_at');
    }
}
