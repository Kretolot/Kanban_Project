<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250612101428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__board AS SELECT id, name, created_at FROM board
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE board
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE board (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, owner_id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
            , CONSTRAINT FK_58562B477E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO board (id, name, created_at) SELECT id, name, created_at FROM __temp__board
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__board
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_58562B477E3C61F9 ON board (owner_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__board AS SELECT id, name, created_at FROM board
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE board
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE board (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
            )
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO board (id, name, created_at) SELECT id, name, created_at FROM __temp__board
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__board
        SQL);
    }
}
