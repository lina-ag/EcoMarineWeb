<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize legacy utilisateur.role column to role relation (role table + utilisateur.id_role FK)';
    }

    public function up(Schema $schema): void
    {
        // Create role table when missing in legacy databases.
        $this->addSql("CREATE TABLE IF NOT EXISTS role (
            id_role INT AUTO_INCREMENT NOT NULL,
            nom_role VARCHAR(50) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            niveau INT NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_57698A6AA5B94004 (nom_role),
            PRIMARY KEY(id_role)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // Seed the canonical roles if they do not exist.
        $this->addSql("INSERT IGNORE INTO role (nom_role, description, niveau, created_at) VALUES
            ('admin', 'Administrateur', 100, NOW()),
            ('chercheur', 'Chercheur', 50, NOW()),
            ('utilisateur', 'Utilisateur', 10, NOW())");

        // Add the FK column expected by Doctrine when missing.
        $this->addSql("SET @has_id_role := (
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'utilisateur' AND COLUMN_NAME = 'id_role'
        )");
        $this->addSql("SET @sql_add_id_role := IF(@has_id_role = 0,
            'ALTER TABLE utilisateur ADD id_role INT DEFAULT NULL',
            'SELECT 1'
        )");
        $this->addSql('PREPARE stmt_add_id_role FROM @sql_add_id_role');
        $this->addSql('EXECUTE stmt_add_id_role');
        $this->addSql('DEALLOCATE PREPARE stmt_add_id_role');

        // Migrate legacy textual role values (including comma-separated values) to id_role.
        $this->addSql("SET @has_legacy_role := (
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'utilisateur' AND COLUMN_NAME = 'role'
        )");
        $this->addSql("SET @sql_migrate_text_role := IF(@has_legacy_role = 1,
            'UPDATE utilisateur u
             LEFT JOIN role r_admin ON r_admin.nom_role = ''admin''
             LEFT JOIN role r_chercheur ON r_chercheur.nom_role = ''chercheur''
             LEFT JOIN role r_utilisateur ON r_utilisateur.nom_role = ''utilisateur''
             SET u.id_role = CASE
                 WHEN u.id_role IS NOT NULL THEN u.id_role
                 WHEN u.role IS NULL OR TRIM(u.role) = '''' THEN NULL
                 WHEN LOWER(u.role) REGEXP ''(^|,)[[:space:]]*admin([[:space:]]*,|$)'' THEN r_admin.id_role
                 WHEN LOWER(u.role) REGEXP ''(^|,)[[:space:]]*chercheur([[:space:]]*,|$)'' THEN r_chercheur.id_role
                 ELSE r_utilisateur.id_role
             END',
            'SELECT 1'
        )");
        $this->addSql('PREPARE stmt_migrate_text_role FROM @sql_migrate_text_role');
        $this->addSql('EXECUTE stmt_migrate_text_role');
        $this->addSql('DEALLOCATE PREPARE stmt_migrate_text_role');

        // Support edge-case legacy data where role was stored as numeric string.
        $this->addSql("SET @sql_migrate_numeric_role := IF(@has_legacy_role = 1,
            'UPDATE utilisateur u
             SET u.id_role = CAST(u.role AS UNSIGNED)
             WHERE u.id_role IS NULL
               AND u.role REGEXP ''^[0-9]+$''
               AND EXISTS (SELECT 1 FROM role r WHERE r.id_role = CAST(u.role AS UNSIGNED))',
            'SELECT 1'
        )");
        $this->addSql('PREPARE stmt_migrate_numeric_role FROM @sql_migrate_numeric_role');
        $this->addSql('EXECUTE stmt_migrate_numeric_role');
        $this->addSql('DEALLOCATE PREPARE stmt_migrate_numeric_role');

        // Ensure index exists on utilisateur.id_role.
        $this->addSql("SET @has_idx_id_role := (
            SELECT COUNT(*)
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'utilisateur' AND INDEX_NAME = 'IDX_1D1C63B3DC499668'
        )");
        $this->addSql("SET @sql_add_idx_id_role := IF(@has_idx_id_role = 0,
            'CREATE INDEX IDX_1D1C63B3DC499668 ON utilisateur (id_role)',
            'SELECT 1'
        )");
        $this->addSql('PREPARE stmt_add_idx_id_role FROM @sql_add_idx_id_role');
        $this->addSql('EXECUTE stmt_add_idx_id_role');
        $this->addSql('DEALLOCATE PREPARE stmt_add_idx_id_role');

        // Ensure foreign key exists from utilisateur.id_role to role.id_role.
        $this->addSql("SET @has_fk_id_role := (
            SELECT COUNT(*)
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'FK_1D1C63B3DC499668'
        )");
        $this->addSql("SET @sql_add_fk_id_role := IF(@has_fk_id_role = 0,
            'ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3DC499668 FOREIGN KEY (id_role) REFERENCES role (id_role)',
            'SELECT 1'
        )");
        $this->addSql('PREPARE stmt_add_fk_id_role FROM @sql_add_fk_id_role');
        $this->addSql('EXECUTE stmt_add_fk_id_role');
        $this->addSql('DEALLOCATE PREPARE stmt_add_fk_id_role');

        // Drop the legacy role text column once data is migrated.
        $this->addSql("SET @sql_drop_legacy_role := IF(@has_legacy_role = 1,
            'ALTER TABLE utilisateur DROP COLUMN role',
            'SELECT 1'
        )");
        $this->addSql('PREPARE stmt_drop_legacy_role FROM @sql_drop_legacy_role');
        $this->addSql('EXECUTE stmt_drop_legacy_role');
        $this->addSql('DEALLOCATE PREPARE stmt_drop_legacy_role');
    }

    public function down(Schema $schema): void
    {
        // Recreate legacy textual role column and backfill from relation.
        $this->addSql("SET @has_legacy_role := (
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'utilisateur' AND COLUMN_NAME = 'role'
        )");
        $this->addSql("SET @sql_add_legacy_role := IF(@has_legacy_role = 0,
            'ALTER TABLE utilisateur ADD role VARCHAR(255) DEFAULT NULL',
            'SELECT 1'
        )");
        $this->addSql('PREPARE stmt_add_legacy_role FROM @sql_add_legacy_role');
        $this->addSql('EXECUTE stmt_add_legacy_role');
        $this->addSql('DEALLOCATE PREPARE stmt_add_legacy_role');

        $this->addSql("UPDATE utilisateur u
            LEFT JOIN role r ON r.id_role = u.id_role
            SET u.role = r.nom_role
            WHERE u.role IS NULL");

        $this->addSql("SET @has_fk_id_role := (
            SELECT COUNT(*)
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'FK_1D1C63B3DC499668'
        )");
        $this->addSql("SET @sql_drop_fk_id_role := IF(@has_fk_id_role = 1,
            'ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B3DC499668',
            'SELECT 1'
        )");
        $this->addSql('PREPARE stmt_drop_fk_id_role FROM @sql_drop_fk_id_role');
        $this->addSql('EXECUTE stmt_drop_fk_id_role');
        $this->addSql('DEALLOCATE PREPARE stmt_drop_fk_id_role');

        $this->addSql("SET @has_idx_id_role := (
            SELECT COUNT(*)
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'utilisateur' AND INDEX_NAME = 'IDX_1D1C63B3DC499668'
        )");
        $this->addSql("SET @sql_drop_idx_id_role := IF(@has_idx_id_role = 1,
            'DROP INDEX IDX_1D1C63B3DC499668 ON utilisateur',
            'SELECT 1'
        )");
        $this->addSql('PREPARE stmt_drop_idx_id_role FROM @sql_drop_idx_id_role');
        $this->addSql('EXECUTE stmt_drop_idx_id_role');
        $this->addSql('DEALLOCATE PREPARE stmt_drop_idx_id_role');

        $this->addSql("SET @has_id_role := (
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'utilisateur' AND COLUMN_NAME = 'id_role'
        )");
        $this->addSql("SET @sql_drop_id_role := IF(@has_id_role = 1,
            'ALTER TABLE utilisateur DROP COLUMN id_role',
            'SELECT 1'
        )");
        $this->addSql('PREPARE stmt_drop_id_role FROM @sql_drop_id_role');
        $this->addSql('EXECUTE stmt_drop_id_role');
        $this->addSql('DEALLOCATE PREPARE stmt_drop_id_role');
    }
}
