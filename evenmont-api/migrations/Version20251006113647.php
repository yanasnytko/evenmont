<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006113647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD COLUMN capacity INTEGER UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE event_registration ADD COLUMN updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__event AS SELECT id, organizer_id, title, description, start_at, end_at, city, created_at, cover_url FROM event');
        $this->addSql('DROP TABLE event');
        $this->addSql('CREATE TABLE event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, organizer_id INTEGER NOT NULL, title VARCHAR(191) NOT NULL, description CLOB DEFAULT NULL, start_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , end_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , city VARCHAR(120) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , cover_url VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_3BAE0AA7876C4DDA FOREIGN KEY (organizer_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO event (id, organizer_id, title, description, start_at, end_at, city, created_at, cover_url) SELECT id, organizer_id, title, description, start_at, end_at, city, created_at, cover_url FROM __temp__event');
        $this->addSql('DROP TABLE __temp__event');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7876C4DDA ON event (organizer_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__event_registration AS SELECT id, event_id, user_id, status, created_at FROM event_registration');
        $this->addSql('DROP TABLE event_registration');
        $this->addSql('CREATE TABLE event_registration (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, event_id INTEGER NOT NULL, user_id INTEGER NOT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_8FBBAD5471F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8FBBAD54A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO event_registration (id, event_id, user_id, status, created_at) SELECT id, event_id, user_id, status, created_at FROM __temp__event_registration');
        $this->addSql('DROP TABLE __temp__event_registration');
        $this->addSql('CREATE INDEX IDX_8FBBAD5471F7E88B ON event_registration (event_id)');
        $this->addSql('CREATE INDEX IDX_8FBBAD54A76ED395 ON event_registration (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EVENT_USER ON event_registration (event_id, user_id)');
    }
}
