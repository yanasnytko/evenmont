<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006140830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__payment AS SELECT id, registration_id, provider, amount, currency, status, transaction_ref, payload_json, created_at FROM payment');
        $this->addSql('DROP TABLE payment');
        $this->addSql('CREATE TABLE payment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, event_id INTEGER NOT NULL, provider VARCHAR(20) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(20) NOT NULL, transaction_ref VARCHAR(191) DEFAULT NULL, metadata CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , mollie_id VARCHAR(40) DEFAULT NULL, checkout_url VARCHAR(1024) DEFAULT NULL, paid_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_6D28840DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6D28840D71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO payment (id, user_id, provider, amount, currency, status, transaction_ref, metadata, created_at) SELECT id, registration_id, provider, amount, currency, status, transaction_ref, payload_json, created_at FROM __temp__payment');
        $this->addSql('DROP TABLE __temp__payment');
        $this->addSql('CREATE INDEX IDX_6D28840DA76ED395 ON payment (user_id)');
        $this->addSql('CREATE INDEX IDX_6D28840D71F7E88B ON payment (event_id)');
        $this->addSql('CREATE INDEX idx_payment_mollie ON payment (mollie_id)');
        $this->addSql('CREATE INDEX idx_payment_txref ON payment (transaction_ref)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__payment AS SELECT id, transaction_ref, metadata, provider, amount, currency, status, created_at FROM payment');
        $this->addSql('DROP TABLE payment');
        $this->addSql('CREATE TABLE payment (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, registration_id INTEGER NOT NULL, transaction_ref VARCHAR(191) NOT NULL, payload_json CLOB DEFAULT NULL --(DC2Type:json)
        , provider VARCHAR(20) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, CONSTRAINT FK_6D28840D833D8F43 FOREIGN KEY (registration_id) REFERENCES event_registration (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO payment (id, transaction_ref, payload_json, provider, amount, currency, status, created_at) SELECT id, transaction_ref, metadata, provider, amount, currency, status, created_at FROM __temp__payment');
        $this->addSql('DROP TABLE __temp__payment');
        $this->addSql('CREATE INDEX IDX_6D28840D833D8F43 ON payment (registration_id)');
    }
}
