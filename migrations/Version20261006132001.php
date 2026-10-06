<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261006132001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Job search : entités Company et JobApplication, lien JobOffer -> Company';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, normalized_name VARCHAR(255) NOT NULL, city VARCHAR(100) DEFAULT NULL, region VARCHAR(50) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, workforce VARCHAR(50) DEFAULT NULL, remote VARCHAR(50) DEFAULT NULL, technologies LONGTEXT DEFAULT NULL, jobs LONGTEXT DEFAULT NULL, details LONGTEXT DEFAULT NULL, contact_email VARCHAR(255) DEFAULT NULL, application_url LONGTEXT DEFAULT NULL, source VARCHAR(30) NOT NULL, status VARCHAR(30) NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_4FBF094FD69C0128 (normalized_name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE job_application (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(30) NOT NULL, status VARCHAR(30) NOT NULL, sent_at DATETIME DEFAULT NULL, follow_up_at DATETIME DEFAULT NULL, last_followed_up_at DATETIME DEFAULT NULL, follow_up_count INT NOT NULL, cv_version VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, company_id INT DEFAULT NULL, offer_id INT DEFAULT NULL, INDEX IDX_C737C688979B1AD6 (company_id), INDEX IDX_C737C68853C674EE (offer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C688979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C68853C674EE FOREIGN KEY (offer_id) REFERENCES job_offer (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE job_offer ADD linked_company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE job_offer ADD CONSTRAINT FK_288A3A4E5FC191 FOREIGN KEY (linked_company_id) REFERENCES company (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_288A3A4E5FC191 ON job_offer (linked_company_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_application DROP FOREIGN KEY FK_C737C688979B1AD6');
        $this->addSql('ALTER TABLE job_application DROP FOREIGN KEY FK_C737C68853C674EE');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE job_application');
        $this->addSql('ALTER TABLE job_offer DROP FOREIGN KEY FK_288A3A4E5FC191');
        $this->addSql('DROP INDEX IDX_288A3A4E5FC191 ON job_offer');
        $this->addSql('ALTER TABLE job_offer DROP linked_company_id');
    }
}
