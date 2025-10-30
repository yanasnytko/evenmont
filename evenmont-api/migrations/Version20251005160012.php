<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251005160012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__refresh_token AS SELECT refresh_token, valid, username FROM refresh_token');
        $this->addSql('DROP TABLE refresh_token');
        $this->addSql('CREATE TABLE refresh_token (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, refresh_token VARCHAR(128) NOT NULL, valid DATETIME NOT NULL, username VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO refresh_token (refresh_token, valid, username) SELECT refresh_token, valid, username FROM __temp__refresh_token');
        $this->addSql('DROP TABLE __temp__refresh_token');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C74F2195C74F2195 ON refresh_token (refresh_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__refresh_token AS SELECT refresh_token, username, valid FROM refresh_token');
        $this->addSql('DROP TABLE refresh_token');
        $this->addSql('CREATE TABLE refresh_token (refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, PRIMARY KEY(refresh_token))');
        $this->addSql('INSERT INTO refresh_token (refresh_token, username, valid) SELECT refresh_token, username, valid FROM __temp__refresh_token');
        $this->addSql('DROP TABLE __temp__refresh_token');
    }
}
