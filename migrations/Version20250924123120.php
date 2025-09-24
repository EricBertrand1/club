<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924123120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE castellum_question ADD duration_seconds SMALLINT UNSIGNED DEFAULT NULL, ADD formation_chapter VARCHAR(120) DEFAULT NULL, ADD formation_paragraph VARCHAR(120) DEFAULT NULL, ADD question_audio VARCHAR(255) DEFAULT NULL, ADD coord_x INT DEFAULT NULL, ADD coord_y INT DEFAULT NULL, ADD qcm_text1 VARCHAR(255) DEFAULT NULL, ADD qcm_text2 VARCHAR(255) DEFAULT NULL, ADD qcm_text3 VARCHAR(255) DEFAULT NULL, ADD qcm_text4 VARCHAR(255) DEFAULT NULL, ADD qcm_text5 VARCHAR(255) DEFAULT NULL, ADD qcm_text6 VARCHAR(255) DEFAULT NULL, ADD qcm_text7 VARCHAR(255) DEFAULT NULL, ADD qcm_text8 VARCHAR(255) DEFAULT NULL, ADD qcm_text9 VARCHAR(255) DEFAULT NULL, ADD qcm_text10 VARCHAR(255) DEFAULT NULL, ADD qcm_image1 VARCHAR(255) DEFAULT NULL, ADD qcm_image2 VARCHAR(255) DEFAULT NULL, ADD qcm_image3 VARCHAR(255) DEFAULT NULL, ADD qcm_image4 VARCHAR(255) DEFAULT NULL, ADD qcm_image5 VARCHAR(255) DEFAULT NULL, ADD qcm_image6 VARCHAR(255) DEFAULT NULL, ADD qcm_image7 VARCHAR(255) DEFAULT NULL, ADD qcm_image8 VARCHAR(255) DEFAULT NULL, ADD qcm_image9 VARCHAR(255) DEFAULT NULL, ADD qcm_image10 VARCHAR(255) DEFAULT NULL, ADD validation_signataire1 VARCHAR(180) DEFAULT NULL, ADD validation_signataire2 VARCHAR(180) DEFAULT NULL, ADD validation_signataire3 VARCHAR(180) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE castellum_question DROP duration_seconds, DROP formation_chapter, DROP formation_paragraph, DROP question_audio, DROP coord_x, DROP coord_y, DROP qcm_text1, DROP qcm_text2, DROP qcm_text3, DROP qcm_text4, DROP qcm_text5, DROP qcm_text6, DROP qcm_text7, DROP qcm_text8, DROP qcm_text9, DROP qcm_text10, DROP qcm_image1, DROP qcm_image2, DROP qcm_image3, DROP qcm_image4, DROP qcm_image5, DROP qcm_image6, DROP qcm_image7, DROP qcm_image8, DROP qcm_image9, DROP qcm_image10, DROP validation_signataire1, DROP validation_signataire2, DROP validation_signataire3');
    }
}
