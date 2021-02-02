<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210202001459 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_5904BEADDD842E46');
        $this->addSql('DROP INDEX IDX_5904BEAD2989F1FD');
        $this->addSql('CREATE TEMPORARY TABLE __temp__invoice_position AS SELECT invoice_id, position_id FROM invoice_position');
        $this->addSql('DROP TABLE invoice_position');
        $this->addSql('CREATE TABLE invoice_position (invoice_id INTEGER NOT NULL, position_id INTEGER NOT NULL, quantity INTEGER DEFAULT 1, PRIMARY KEY(invoice_id, position_id), CONSTRAINT FK_5904BEAD2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5904BEADDD842E46 FOREIGN KEY (position_id) REFERENCES position (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO invoice_position (invoice_id, position_id) SELECT invoice_id, position_id FROM __temp__invoice_position');
        $this->addSql('DROP TABLE __temp__invoice_position');
        $this->addSql('CREATE INDEX IDX_5904BEADDD842E46 ON invoice_position (position_id)');
        $this->addSql('CREATE INDEX IDX_5904BEAD2989F1FD ON invoice_position (invoice_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_5904BEAD2989F1FD');
        $this->addSql('DROP INDEX IDX_5904BEADDD842E46');
        $this->addSql('CREATE TEMPORARY TABLE __temp__invoice_position AS SELECT invoice_id, position_id FROM invoice_position');
        $this->addSql('DROP TABLE invoice_position');
        $this->addSql('CREATE TABLE invoice_position (invoice_id INTEGER NOT NULL, position_id INTEGER NOT NULL, PRIMARY KEY(invoice_id, position_id))');
        $this->addSql('INSERT INTO invoice_position (invoice_id, position_id) SELECT invoice_id, position_id FROM __temp__invoice_position');
        $this->addSql('DROP TABLE __temp__invoice_position');
        $this->addSql('CREATE INDEX IDX_5904BEAD2989F1FD ON invoice_position (invoice_id)');
        $this->addSql('CREATE INDEX IDX_5904BEADDD842E46 ON invoice_position (position_id)');
    }
}
