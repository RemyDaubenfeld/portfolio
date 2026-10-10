<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261010055737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Job search : note de pertinence des offres et mots-clés à éviter';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE negative_keyword (id INT AUTO_INCREMENT NOT NULL, key_word VARCHAR(100) NOT NULL, active TINYINT NOT NULL, UNIQUE INDEX UNIQ_143B810E45F6AED8 (key_word), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE job_offer ADD relevance_score INT DEFAULT NULL, ADD relevance_details JSON DEFAULT NULL');

        // Motifs récurrents des offres écartées jusqu'ici (modifiables dans l'admin)
        $this->addSql("INSERT INTO negative_keyword (key_word, active) VALUES
            ('java', 1), ('c#', 1), ('.net', 1), ('windev', 1),
            ('alternance', 1), ('stage', 1), ('intern', 1),
            ('senior', 1), ('architecte', 1),
            ('front', 1), ('front-end', 1), ('intégrateur', 1)");
        // Les notes des offres existantes sont calculées au premier affichage du tableau de bord
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE negative_keyword');
        $this->addSql('ALTER TABLE job_offer DROP relevance_score, DROP relevance_details');
    }
}
