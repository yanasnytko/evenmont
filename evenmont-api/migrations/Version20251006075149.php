<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006075149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, password, roles, first_name, last_name, created_at, avatar_url, email_verified_at, verify_token, verify_token_expires_at FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , first_name VARCHAR(100) DEFAULT NULL, last_name VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , avatar_url VARCHAR(255) DEFAULT NULL, email_verified_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , verify_token VARCHAR(100) DEFAULT NULL, verify_token_expires_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('INSERT INTO user (id, email, password, roles, first_name, last_name, created_at, avatar_url, email_verified_at, verify_token, verify_token_expires_at) SELECT id, email, password, roles, first_name, last_name, created_at, avatar_url, email_verified_at, verify_token, verify_token_expires_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64998E46A9B ON user (verify_token)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, password, roles, first_name, last_name, created_at, avatar_url, email_verified_at, verify_token, verify_token_expires_at FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , first_name VARCHAR(100) DEFAULT NULL, last_name VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , avatar_url VARCHAR(255) DEFAULT NULL, email_verified_at DATETIME DEFAULT NULL, verify_token VARCHAR(100) DEFAULT NULL, verify_token_expires_at DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO user (id, email, password, roles, first_name, last_name, created_at, avatar_url, email_verified_at, verify_token, verify_token_expires_at) SELECT id, email, password, roles, first_name, last_name, created_at, avatar_url, email_verified_at, verify_token, verify_token_expires_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64998E46A9B ON user (verify_token)');
    }
}
