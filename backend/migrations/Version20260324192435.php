<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260324192435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create doctors and time_slots tables (UUID PKs, FK doctor_id).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE doctors (id UUID NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, specialty VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE time_slots (id UUID NOT NULL, start_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_booked BOOLEAN DEFAULT false NOT NULL, patient_email VARCHAR(320) DEFAULT NULL, doctor_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_8D06D4AC87F4FB17 ON time_slots (doctor_id)');
        $this->addSql('ALTER TABLE time_slots ADD CONSTRAINT FK_8D06D4AC87F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE time_slots DROP CONSTRAINT FK_8D06D4AC87F4FB17');
        $this->addSql('DROP TABLE doctors');
        $this->addSql('DROP TABLE time_slots');
    }
}
