<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250611083555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Add composite indexes for better query performance
        $this->addSql('CREATE INDEX IDX_58562B47_OWNER_CREATED ON board (owner_id, created_at DESC)');
        $this->addSql('CREATE INDEX IDX_13B1F670_BOARD_POSITION ON col (board_id, position ASC)');
        $this->addSql('CREATE INDEX IDX_527EDB25_COL_POSITION ON task (col_id, position ASC)');
        $this->addSql('CREATE INDEX IDX_527EDB25_COL_CREATED ON task (col_id, created_at DESC)');
        
        // For SQLite - regular indexes for search
        $this->addSql('CREATE INDEX IDX_527EDB25_TITLE ON task (title)');
        $this->addSql('CREATE INDEX IDX_8D93D649_EMAIL ON user (email)');
        $this->addSql('CREATE INDEX IDX_8D93D649_VERIFIED ON user (is_verified)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_58562B47_OWNER_CREATED');
        $this->addSql('DROP INDEX IDX_13B1F670_BOARD_POSITION');
        $this->addSql('DROP INDEX IDX_527EDB25_COL_POSITION');
        $this->addSql('DROP INDEX IDX_527EDB25_COL_CREATED');
        $this->addSql('DROP INDEX IDX_527EDB25_TITLE');
        $this->addSql('DROP INDEX IDX_8D93D649_EMAIL');
        $this->addSql('DROP INDEX IDX_8D93D649_VERIFIED');
    }
}
