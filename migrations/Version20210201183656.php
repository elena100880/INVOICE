<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210201183656 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invoice_position (invoice_id INTEGER NOT NULL, position_id INTEGER NOT NULL, PRIMARY KEY(invoice_id, position_id))');
        $this->addSql('CREATE INDEX IDX_5904BEAD2989F1FD ON invoice_position (invoice_id)');
        $this->addSql('CREATE INDEX IDX_5904BEADDD842E46 ON invoice_position (position_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE invoice_position');
    }
}
