<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261006183408 extends AbstractMigration
{
    private const STARTER_TEMPLATE = <<<'TXT'
Actuellement en fin de reconversion dans le développement web, je vous adresse ma candidature pour le poste de {poste} au sein de {entreprise}.

Orienté back-end, je travaille principalement avec PHP et Symfony. Lors de mon stage chez VPDive, j'ai développé sur une plateforme Symfony en production utilisée par plus de 1000 clubs de plongée : génération automatisée de données structurées pour le référencement, intégration à Google Search Console et à Matomo. En parallèle, je conçois mes propres outils (routeur de modèles d'IA locaux, benchmark de LLM, automatisations avec n8n), ce qui me permet d'approfondir l'architecture, Docker et l'automatisation.

Mes dix années dans l'animation jeunesse, dont quatre à la direction d'un service, m'ont apporté le sens de l'organisation, de la communication et du travail en équipe, des qualités que je souhaite mettre au service de vos projets.

Je serais ravi d'échanger avec vous lors d'un entretien pour vous présenter plus en détail mon parcours et ma motivation.
TXT;

    public function getDescription(): string
    {
        return 'Job search : historique des candidatures, lettres de motivation et modèles de lettre';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cover_letter_template (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, body LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE job_application_event (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(30) NOT NULL, status VARCHAR(30) NOT NULL, occurred_at DATETIME NOT NULL, comment VARCHAR(255) DEFAULT NULL, application_id INT NOT NULL, INDEX IDX_DC3C2D283E030ACD (application_id), INDEX IDX_DC3C2D2887C03D1B (occurred_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE job_application_event ADD CONSTRAINT FK_DC3C2D283E030ACD FOREIGN KEY (application_id) REFERENCES job_application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_application ADD cover_letter LONGTEXT DEFAULT NULL');

        // --- Historique initial des candidatures existantes, reconstitué à partir de leurs dates ---
        // Création : datée de l'envoi (ou de la saisie pour un brouillon)
        $this->addSql("INSERT INTO job_application_event (application_id, type, status, occurred_at)
            SELECT id, 'created', IF(status = 'draft', 'draft', 'sent'), IF(status = 'draft', created_at, COALESCE(sent_at, created_at))
            FROM job_application");
        // Dernière relance connue
        $this->addSql("INSERT INTO job_application_event (application_id, type, status, occurred_at, comment)
            SELECT id, 'followed_up', 'followed_up', last_followed_up_at, CONCAT('Relance n°', follow_up_count)
            FROM job_application
            WHERE follow_up_count > 0 AND last_followed_up_at IS NOT NULL");
        // Statut final s'il va au-delà de l'envoi (date réelle inconnue : dernière modification)
        $this->addSql("INSERT INTO job_application_event (application_id, type, status, occurred_at)
            SELECT id, 'status_changed', status, COALESCE(updated_at, created_at)
            FROM job_application
            WHERE status NOT IN ('draft', 'sent', 'followed_up')");

        // --- Premier modèle de lettre, à adapter dans l'admin ---
        $this->addSql('INSERT INTO cover_letter_template (name, body) VALUES (?, ?)', ['Générique', self::STARTER_TEMPLATE]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_application_event DROP FOREIGN KEY FK_DC3C2D283E030ACD');
        $this->addSql('DROP TABLE cover_letter_template');
        $this->addSql('DROP TABLE job_application_event');
        $this->addSql('ALTER TABLE job_application DROP cover_letter');
    }
}
