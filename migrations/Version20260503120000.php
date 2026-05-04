<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add quiz_badge column to reservation table
 */
final class Version20260503120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add quiz_badge column to reservation table for tracking quiz results';
    }

    public function up(Schema $schema): void
    {
        // Add quiz_badge column to reservation table if it doesn't exist
        $this->addSql("ALTER TABLE reservation ADD COLUMN quiz_badge VARCHAR(255) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        // Remove quiz_badge column
        $this->addSql("ALTER TABLE reservation DROP COLUMN quiz_badge");
    }
}
