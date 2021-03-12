<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210312114607 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invoice (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, supplier_id INTEGER NOT NULL, recipient_id INTEGER NOT NULL)');
        $this->addSql('CREATE INDEX IDX_906517442ADD6D8C ON invoice (supplier_id)');
        $this->addSql('CREATE INDEX IDX_90651744E92F8F78 ON invoice (recipient_id)');
        $this->addSql('CREATE TABLE invoice_position (invoice_id INTEGER NOT NULL, position_id INTEGER NOT NULL, quantity INTEGER NOT NULL, PRIMARY KEY(invoice_id, position_id))');
        $this->addSql('CREATE INDEX IDX_5904BEAD2989F1FD ON invoice_position (invoice_id)');
        $this->addSql('CREATE INDEX IDX_5904BEADDD842E46 ON invoice_position (position_id)');
        $this->addSql('CREATE TABLE position (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, value DOUBLE PRECISION NOT NULL)');
        $this->addSql('CREATE TABLE recipient (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, family VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE supplier (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, nip INTEGER NOT NULL)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE invoice_position');
        $this->addSql('DROP TABLE position');
        $this->addSql('DROP TABLE recipient');
        $this->addSql('DROP TABLE supplier');
    }
}
