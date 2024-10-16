<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241016094153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE base (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(128) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, zip_code VARCHAR(10) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, city_id INT NOT NULL, name VARCHAR(255) NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, street_number INT NOT NULL, street_name VARCHAR(255) NOT NULL, INDEX IDX_5E9E89CB8BAC62AF (city_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE participant (id INT AUTO_INCREMENT NOT NULL, base_id INT NOT NULL, mail VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, firstname VARCHAR(128) NOT NULL, lastname VARCHAR(128) NOT NULL, phone_number VARCHAR(20) DEFAULT NULL, is_admin TINYINT(1) NOT NULL, is_active TINYINT(1) NOT NULL, username VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_D79F6B11F85E0677 (username), INDEX IDX_D79F6B116967DF41 (base_id), UNIQUE INDEX UNIQ_IDENTIFIER_MAIL (mail), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE participant_trip (participant_id INT NOT NULL, trip_id INT NOT NULL, INDEX IDX_A2E2E7059D1C3019 (participant_id), INDEX IDX_A2E2E705A5BC2E0E (trip_id), PRIMARY KEY(participant_id, trip_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE state (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trip (id INT AUTO_INCREMENT NOT NULL, location_id INT NOT NULL, state_id INT NOT NULL, base_id INT NOT NULL, organiser_id INT NOT NULL, name VARCHAR(255) NOT NULL, date_hour_start DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', duration INT NOT NULL, date_registration_limit DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', num_max_registration INT NOT NULL, trip_details LONGTEXT DEFAULT NULL, is_archived TINYINT(1) NOT NULL, reason_cancel LONGTEXT DEFAULT NULL, INDEX IDX_7656F53B64D218E (location_id), INDEX IDX_7656F53B5D83CC1 (state_id), INDEX IDX_7656F53B6967DF41 (base_id), INDEX IDX_7656F53BA0631C12 (organiser_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB8BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE participant ADD CONSTRAINT FK_D79F6B116967DF41 FOREIGN KEY (base_id) REFERENCES base (id)');
        $this->addSql('ALTER TABLE participant_trip ADD CONSTRAINT FK_A2E2E7059D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participant_trip ADD CONSTRAINT FK_A2E2E705A5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53B64D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53B5D83CC1 FOREIGN KEY (state_id) REFERENCES state (id)');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53B6967DF41 FOREIGN KEY (base_id) REFERENCES base (id)');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53BA0631C12 FOREIGN KEY (organiser_id) REFERENCES participant (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CB8BAC62AF');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B116967DF41');
        $this->addSql('ALTER TABLE participant_trip DROP FOREIGN KEY FK_A2E2E7059D1C3019');
        $this->addSql('ALTER TABLE participant_trip DROP FOREIGN KEY FK_A2E2E705A5BC2E0E');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53B64D218E');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53B5D83CC1');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53B6967DF41');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53BA0631C12');
        $this->addSql('DROP TABLE base');
        $this->addSql('DROP TABLE city');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE participant');
        $this->addSql('DROP TABLE participant_trip');
        $this->addSql('DROP TABLE state');
        $this->addSql('DROP TABLE trip');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
