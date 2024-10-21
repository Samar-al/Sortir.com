<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241021113912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `group` (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(128) NOT NULL, is_private TINYINT(1) NOT NULL, INDEX IDX_6DC044C57E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE group_participant (group_id INT NOT NULL, participant_id INT NOT NULL, INDEX IDX_F22774D0FE54D947 (group_id), INDEX IDX_F22774D09D1C3019 (participant_id), PRIMARY KEY(group_id, participant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `group` ADD CONSTRAINT FK_6DC044C57E3C61F9 FOREIGN KEY (owner_id) REFERENCES participant (id)');
        $this->addSql('ALTER TABLE group_participant ADD CONSTRAINT FK_F22774D0FE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE group_participant ADD CONSTRAINT FK_F22774D09D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `group` DROP FOREIGN KEY FK_6DC044C57E3C61F9');
        $this->addSql('ALTER TABLE group_participant DROP FOREIGN KEY FK_F22774D0FE54D947');
        $this->addSql('ALTER TABLE group_participant DROP FOREIGN KEY FK_F22774D09D1C3019');
        $this->addSql('DROP TABLE `group`');
        $this->addSql('DROP TABLE group_participant');
    }
}
