<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251004133006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, event_id INTEGER NOT NULL, user_id INTEGER NOT NULL, content CLOB NOT NULL, status VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, CONSTRAINT FK_9474526C71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_9474526C71F7E88B ON comment (event_id)');
        $this->addSql('CREATE INDEX IDX_9474526CA76ED395 ON comment (user_id)');
        $this->addSql('CREATE TABLE comment_report (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, comment_id INTEGER NOT NULL, reporter_id INTEGER NOT NULL, handled_by_id INTEGER DEFAULT NULL, reason VARCHAR(255) DEFAULT NULL, status VARCHAR(10) NOT NULL, handled_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_E3C2F96F8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E3C2F96E1CFE6F5 FOREIGN KEY (reporter_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E3C2F96FE65AF40 FOREIGN KEY (handled_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E3C2F96F8697D13 ON comment_report (comment_id)');
        $this->addSql('CREATE INDEX IDX_E3C2F96E1CFE6F5 ON comment_report (reporter_id)');
        $this->addSql('CREATE INDEX IDX_E3C2F96FE65AF40 ON comment_report (handled_by_id)');
        $this->addSql('CREATE TABLE consent (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, type VARCHAR(20) NOT NULL, granted BOOLEAN NOT NULL, version VARCHAR(20) DEFAULT NULL, granted_at DATETIME NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, CONSTRAINT FK_63120810A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_63120810A76ED395 ON consent (user_id)');
        $this->addSql('CREATE TABLE country (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(2) NOT NULL, name VARCHAR(128) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE TABLE event_tag (event_id INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY(event_id, tag_id), CONSTRAINT FK_1246725071F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_12467250BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1246725071F7E88B ON event_tag (event_id)');
        $this->addSql('CREATE INDEX IDX_12467250BAD26311 ON event_tag (tag_id)');
        $this->addSql('CREATE TABLE favorite (user_id INTEGER NOT NULL, event_id INTEGER NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(user_id, event_id), CONSTRAINT FK_68C58ED9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_68C58ED971F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_68C58ED9A76ED395 ON favorite (user_id)');
        $this->addSql('CREATE INDEX IDX_68C58ED971F7E88B ON favorite (event_id)');
        $this->addSql('CREATE TABLE language (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(8) NOT NULL, name VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE TABLE newsletter (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER DEFAULT NULL, language_id INTEGER DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, subscribed_at DATETIME NOT NULL, unsubscribed_at DATETIME DEFAULT NULL, CONSTRAINT FK_7E8585C8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7E8585C882F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_7E8585C8A76ED395 ON newsletter (user_id)');
        $this->addSql('CREATE INDEX IDX_7E8585C882F1BAF4 ON newsletter (language_id)');
        $this->addSql('CREATE TABLE notification (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, event_id INTEGER DEFAULT NULL, type VARCHAR(10) NOT NULL, template_key VARCHAR(120) DEFAULT NULL, title VARCHAR(191) DEFAULT NULL, message CLOB DEFAULT NULL, data_json CLOB DEFAULT NULL --(DC2Type:json)
        , scheduled_at DATETIME DEFAULT NULL, sent_at DATETIME DEFAULT NULL, channel_status VARCHAR(10) DEFAULT NULL, seen_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_BF5476CA71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_BF5476CAA76ED395 ON notification (user_id)');
        $this->addSql('CREATE INDEX IDX_BF5476CA71F7E88B ON notification (event_id)');
        $this->addSql('CREATE TABLE payment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, registration_id INTEGER NOT NULL, provider VARCHAR(20) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(20) NOT NULL, transaction_ref VARCHAR(191) NOT NULL, payload_json CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL, CONSTRAINT FK_6D28840D833D8F43 FOREIGN KEY (registration_id) REFERENCES event_registration (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6D28840D833D8F43 ON payment (registration_id)');
        $this->addSql('CREATE TABLE role (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, description VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE TABLE tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(120) NOT NULL)');
        $this->addSql('CREATE TABLE ticket (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, registration_id INTEGER NOT NULL, ticket_type_id INTEGER NOT NULL, qr_code VARCHAR(255) DEFAULT NULL, status VARCHAR(10) NOT NULL, issued_at DATETIME NOT NULL, CONSTRAINT FK_97A0ADA3833D8F43 FOREIGN KEY (registration_id) REFERENCES event_registration (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_97A0ADA3C980D5C1 FOREIGN KEY (ticket_type_id) REFERENCES ticket_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_97A0ADA3833D8F43 ON ticket (registration_id)');
        $this->addSql('CREATE INDEX IDX_97A0ADA3C980D5C1 ON ticket (ticket_type_id)');
        $this->addSql('CREATE TABLE ticket_type (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, event_id INTEGER NOT NULL, name VARCHAR(120) NOT NULL, price NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, quantity_total INTEGER NOT NULL, quantity_sold INTEGER NOT NULL, sales_start_at DATETIME DEFAULT NULL, sales_end_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, CONSTRAINT FK_BE05421171F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_BE05421171F7E88B ON ticket_type (event_id)');
        $this->addSql('CREATE TABLE user_role (user_id INTEGER NOT NULL, role_id INTEGER NOT NULL, PRIMARY KEY(user_id, role_id), CONSTRAINT FK_2DE8C6A3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2DE8C6A3D60322AC FOREIGN KEY (role_id) REFERENCES role (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_2DE8C6A3A76ED395 ON user_role (user_id)');
        $this->addSql('CREATE INDEX IDX_2DE8C6A3D60322AC ON user_role (role_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE comment_report');
        $this->addSql('DROP TABLE consent');
        $this->addSql('DROP TABLE country');
        $this->addSql('DROP TABLE event_tag');
        $this->addSql('DROP TABLE favorite');
        $this->addSql('DROP TABLE language');
        $this->addSql('DROP TABLE newsletter');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('DROP TABLE ticket_type');
        $this->addSql('DROP TABLE user_role');
    }
}
