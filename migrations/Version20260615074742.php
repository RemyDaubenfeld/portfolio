<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260615074742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD bio_headline LONGTEXT DEFAULT NULL, ADD bio_background LONGTEXT DEFAULT NULL, ADD bio_objective LONGTEXT DEFAULT NULL, DROP bio_short, DROP bio_long, DROP bio_skills');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD bio_short LONGTEXT DEFAULT NULL, ADD bio_long LONGTEXT DEFAULT NULL, ADD bio_skills LONGTEXT DEFAULT NULL, DROP bio_headline, DROP bio_background, DROP bio_objective');
    }
}
