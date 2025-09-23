<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250921142914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE coaching_theme (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, section VARCHAR(50) NOT NULL, label VARCHAR(100) DEFAULT NULL, position INT NOT NULL, INDEX IDX_674A15AEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rubrique (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, label VARCHAR(100) NOT NULL, img VARCHAR(255) DEFAULT NULL, INDEX IDX_8FA4097CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE theme (id INT AUTO_INCREMENT NOT NULL, rubrique_id INT DEFAULT NULL, label VARCHAR(100) DEFAULT NULL, INDEX IDX_9775E7083BD38833 (rubrique_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_check (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, theme_id INT NOT NULL, day DATE NOT NULL, checked TINYINT(1) NOT NULL, INDEX IDX_DE4C574BA76ED395 (user_id), INDEX IDX_DE4C574B59027487 (theme_id), UNIQUE INDEX UNIQ_DE4C574BA76ED395E5A0299059027487 (user_id, day, theme_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_theme (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, section VARCHAR(32) NOT NULL, position INT NOT NULL, label VARCHAR(120) DEFAULT NULL, coefficient INT NOT NULL, INDEX IDX_75B71C50A76ED395 (user_id), UNIQUE INDEX UNIQ_75B71C50A76ED3952D737AEF462CE4F5 (user_id, section, position), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE coaching_theme ADD CONSTRAINT FK_674A15AEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE rubrique ADD CONSTRAINT FK_8FA4097CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE theme ADD CONSTRAINT FK_9775E7083BD38833 FOREIGN KEY (rubrique_id) REFERENCES rubrique (id)');
        $this->addSql('ALTER TABLE user_check ADD CONSTRAINT FK_DE4C574BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_check ADD CONSTRAINT FK_DE4C574B59027487 FOREIGN KEY (theme_id) REFERENCES user_theme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_theme ADD CONSTRAINT FK_75B71C50A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coaching_theme DROP FOREIGN KEY FK_674A15AEA76ED395');
        $this->addSql('ALTER TABLE rubrique DROP FOREIGN KEY FK_8FA4097CA76ED395');
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E7083BD38833');
        $this->addSql('ALTER TABLE user_check DROP FOREIGN KEY FK_DE4C574BA76ED395');
        $this->addSql('ALTER TABLE user_check DROP FOREIGN KEY FK_DE4C574B59027487');
        $this->addSql('ALTER TABLE user_theme DROP FOREIGN KEY FK_75B71C50A76ED395');
        $this->addSql('DROP TABLE coaching_theme');
        $this->addSql('DROP TABLE rubrique');
        $this->addSql('DROP TABLE theme');
        $this->addSql('DROP TABLE user_check');
        $this->addSql('DROP TABLE user_theme');
    }
}
