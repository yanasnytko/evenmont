<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251005104744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__event AS SELECT id, organizer_id, title, description, start_at, end_at, city, created_at, cover_url FROM event');
        $this->addSql('DROP TABLE event');
        $this->addSql('CREATE TABLE event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, organizer_id INTEGER NOT NULL, title VARCHAR(191) NOT NULL, description CLOB DEFAULT NULL, start_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , end_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , city VARCHAR(120) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , cover_url VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_3BAE0AA7876C4DDA FOREIGN KEY (organizer_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO event (id, organizer_id, title, description, start_at, end_at, city, created_at, cover_url) SELECT id, organizer_id, title, description, start_at, end_at, city, created_at, cover_url FROM __temp__event');
        $this->addSql('DROP TABLE __temp__event');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7876C4DDA ON event (organizer_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__event_registration AS SELECT id, event_id, user_id, status, created_at FROM event_registration');
        $this->addSql('DROP TABLE event_registration');
        $this->addSql('CREATE TABLE event_registration (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, event_id INTEGER NOT NULL, user_id INTEGER NOT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_8FBBAD5471F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8FBBAD54A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO event_registration (id, event_id, user_id, status, created_at) SELECT id, event_id, user_id, status, created_at FROM __temp__event_registration');
        $this->addSql('DROP TABLE __temp__event_registration');
        $this->addSql('CREATE INDEX IDX_8FBBAD54A76ED395 ON event_registration (user_id)');
        $this->addSql('CREATE INDEX IDX_8FBBAD5471F7E88B ON event_registration (event_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EVENT_USER ON event_registration (event_id, user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__event_tag AS SELECT event_id, tag_id FROM event_tag');
        $this->addSql('DROP TABLE event_tag');
        $this->addSql('CREATE TABLE event_tag (event_id INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY(event_id, tag_id), CONSTRAINT FK_1246725071F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_12467250BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO event_tag (event_id, tag_id) SELECT event_id, tag_id FROM __temp__event_tag');
        $this->addSql('DROP TABLE __temp__event_tag');
        $this->addSql('CREATE INDEX IDX_12467250BAD26311 ON event_tag (tag_id)');
        $this->addSql('CREATE INDEX IDX_1246725071F7E88B ON event_tag (event_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EVENT_TAG ON event_tag (event_id, tag_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__favorite AS SELECT user_id, event_id, created_at FROM favorite');
        $this->addSql('DROP TABLE favorite');
        $this->addSql('CREATE TABLE favorite (user_id INTEGER NOT NULL, event_id INTEGER NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(user_id, event_id), CONSTRAINT FK_68C58ED9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_68C58ED971F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO favorite (user_id, event_id, created_at) SELECT user_id, event_id, created_at FROM __temp__favorite');
        $this->addSql('DROP TABLE __temp__favorite');
        $this->addSql('CREATE INDEX IDX_68C58ED971F7E88B ON favorite (event_id)');
        $this->addSql('CREATE INDEX IDX_68C58ED9A76ED395 ON favorite (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_EVENT ON favorite (user_id, event_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__newsletter AS SELECT id, user_id, language_id, email, subscribed_at, unsubscribed_at FROM newsletter');
        $this->addSql('DROP TABLE newsletter');
        $this->addSql('CREATE TABLE newsletter (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER DEFAULT NULL, language_id INTEGER DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, subscribed_at DATETIME NOT NULL, unsubscribed_at DATETIME DEFAULT NULL, CONSTRAINT FK_7E8585C8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7E8585C882F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO newsletter (id, user_id, language_id, email, subscribed_at, unsubscribed_at) SELECT id, user_id, language_id, email, subscribed_at, unsubscribed_at FROM __temp__newsletter');
        $this->addSql('DROP TABLE __temp__newsletter');
        $this->addSql('CREATE INDEX IDX_7E8585C882F1BAF4 ON newsletter (language_id)');
        $this->addSql('CREATE INDEX IDX_7E8585C8A76ED395 ON newsletter (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7E8585C8E7927C74 ON newsletter (email)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tag AS SELECT id, name, slug FROM tag');
        $this->addSql('DROP TABLE tag');
        $this->addSql('CREATE TABLE tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(120) NOT NULL)');
        $this->addSql('INSERT INTO tag (id, name, slug) SELECT id, name, slug FROM __temp__tag');
        $this->addSql('DROP TABLE __temp__tag');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_389B783989D9B62 ON tag (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__event AS SELECT id, organizer_id, title, description, start_at, end_at, city, created_at, cover_url FROM event');
        $this->addSql('DROP TABLE event');
        $this->addSql('CREATE TABLE event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, organizer_id INTEGER NOT NULL, title VARCHAR(191) NOT NULL, description CLOB DEFAULT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, city VARCHAR(120) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , cover_url VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_3BAE0AA7876C4DDA FOREIGN KEY (organizer_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO event (id, organizer_id, title, description, start_at, end_at, city, created_at, cover_url) SELECT id, organizer_id, title, description, start_at, end_at, city, created_at, cover_url FROM __temp__event');
        $this->addSql('DROP TABLE __temp__event');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7876C4DDA ON event (organizer_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__event_registration AS SELECT id, event_id, user_id, status, created_at FROM event_registration');
        $this->addSql('DROP TABLE event_registration');
        $this->addSql('CREATE TABLE event_registration (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, event_id INTEGER DEFAULT NULL, user_id INTEGER DEFAULT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_8FBBAD5471F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8FBBAD54A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO event_registration (id, event_id, user_id, status, created_at) SELECT id, event_id, user_id, status, created_at FROM __temp__event_registration');
        $this->addSql('DROP TABLE __temp__event_registration');
        $this->addSql('CREATE INDEX IDX_8FBBAD5471F7E88B ON event_registration (event_id)');
        $this->addSql('CREATE INDEX IDX_8FBBAD54A76ED395 ON event_registration (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8FBBAD5471F7E88BA76ED395 ON event_registration (event_id, user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__event_tag AS SELECT event_id, tag_id FROM event_tag');
        $this->addSql('DROP TABLE event_tag');
        $this->addSql('CREATE TABLE event_tag (event_id INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY(event_id, tag_id), CONSTRAINT FK_1246725071F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_12467250BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO event_tag (event_id, tag_id) SELECT event_id, tag_id FROM __temp__event_tag');
        $this->addSql('DROP TABLE __temp__event_tag');
        $this->addSql('CREATE INDEX IDX_1246725071F7E88B ON event_tag (event_id)');
        $this->addSql('CREATE INDEX IDX_12467250BAD26311 ON event_tag (tag_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__favorite AS SELECT user_id, event_id, created_at FROM favorite');
        $this->addSql('DROP TABLE favorite');
        $this->addSql('CREATE TABLE favorite (user_id INTEGER NOT NULL, event_id INTEGER NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(user_id, event_id), CONSTRAINT FK_68C58ED9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_68C58ED971F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO favorite (user_id, event_id, created_at) SELECT user_id, event_id, created_at FROM __temp__favorite');
        $this->addSql('DROP TABLE __temp__favorite');
        $this->addSql('CREATE INDEX IDX_68C58ED9A76ED395 ON favorite (user_id)');
        $this->addSql('CREATE INDEX IDX_68C58ED971F7E88B ON favorite (event_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__newsletter AS SELECT id, user_id, language_id, email, subscribed_at, unsubscribed_at FROM newsletter');
        $this->addSql('DROP TABLE newsletter');
        $this->addSql('CREATE TABLE newsletter (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER DEFAULT NULL, language_id INTEGER DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, subscribed_at DATETIME NOT NULL, unsubscribed_at DATETIME DEFAULT NULL, CONSTRAINT FK_7E8585C8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7E8585C882F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO newsletter (id, user_id, language_id, email, subscribed_at, unsubscribed_at) SELECT id, user_id, language_id, email, subscribed_at, unsubscribed_at FROM __temp__newsletter');
        $this->addSql('DROP TABLE __temp__newsletter');
        $this->addSql('CREATE INDEX IDX_7E8585C8A76ED395 ON newsletter (user_id)');
        $this->addSql('CREATE INDEX IDX_7E8585C882F1BAF4 ON newsletter (language_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tag AS SELECT id, name, slug FROM tag');
        $this->addSql('DROP TABLE tag');
        $this->addSql('CREATE TABLE tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(120) NOT NULL)');
        $this->addSql('INSERT INTO tag (id, name, slug) SELECT id, name, slug FROM __temp__tag');
        $this->addSql('DROP TABLE __temp__tag');
    }
}
