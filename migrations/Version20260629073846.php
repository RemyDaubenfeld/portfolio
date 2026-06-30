<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260629073846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE interest (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, show_on_portfolio TINYINT DEFAULT 1 NOT NULL, show_on_cv TINYINT DEFAULT 1 NOT NULL, sort_order INT DEFAULT 0 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user ADD cv_summary LONGTEXT DEFAULT NULL, DROP interests, DROP cv_file');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE interest');
        $this->addSql('ALTER TABLE user ADD interests JSON DEFAULT NULL, ADD cv_file VARCHAR(255) DEFAULT \'CV_DAUBENFELD_Rémy.pdf\', DROP cv_summary');
    }
}
