<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250922113156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE castellum_question (id INT AUTO_INCREMENT NOT NULL, subcategory_id INT NOT NULL, category_code VARCHAR(3) NOT NULL, level_question VARCHAR(10) NOT NULL, subject VARCHAR(120) DEFAULT NULL, question_type VARCHAR(50) NOT NULL, question_text LONGTEXT NOT NULL, question_image VARCHAR(255) DEFAULT NULL, answer_text LONGTEXT NOT NULL, explanation LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_80A6348F5DC6FE57 (subcategory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE castellum_subcategory (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(3) NOT NULL, name VARCHAR(150) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_castellum_sub (code, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE castellum_question ADD CONSTRAINT FK_80A6348F5DC6FE57 FOREIGN KEY (subcategory_id) REFERENCES castellum_subcategory (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE castellum_question DROP FOREIGN KEY FK_80A6348F5DC6FE57');
        $this->addSql('DROP TABLE castellum_question');
        $this->addSql('DROP TABLE castellum_subcategory');
    }
}
