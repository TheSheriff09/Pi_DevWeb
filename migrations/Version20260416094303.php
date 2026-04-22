<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416094303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reclamations CHANGE description description LONGTEXT NOT NULL, CHANGE status status VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE responses DROP FOREIGN KEY `fk_response_reclamation`');
        $this->addSql('ALTER TABLE responses DROP FOREIGN KEY `fk_response_user`');
        $this->addSql('DROP INDEX fk_response_reclamation ON responses');
        $this->addSql('DROP INDEX fk_response_user ON responses');
        $this->addSql('ALTER TABLE responses CHANGE content content LONGTEXT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE schedule CHANGE isBooked `isBooked` TINYINT NOT NULL, ADD PRIMARY KEY (`scheduleID`)');
        $this->addSql('ALTER TABLE session CHANGE startupID `startupID` INT NOT NULL, CHANGE sessionType `sessionType` VARCHAR(255) NOT NULL, CHANGE status `status` VARCHAR(255) NOT NULL, CHANGE notes `notes` LONGTEXT DEFAULT NULL, CHANGE successProbability `successProbability` DOUBLE PRECISION DEFAULT NULL, ADD PRIMARY KEY (`sessionID`)');
        $this->addSql('ALTER TABLE session_feedback ADD feedback_id INT NOT NULL, ADD session_id INT NOT NULL, ADD mentor_id INT NOT NULL, ADD progress_score INT NOT NULL, ADD next_actions LONGTEXT DEFAULT NULL, DROP feedbackID, DROP sessionID, DROP mentorID, DROP progressScore, DROP nextActions, CHANGE strengths strengths LONGTEXT DEFAULT NULL, CHANGE weaknesses weaknesses LONGTEXT DEFAULT NULL, CHANGE recommendations recommendations LONGTEXT DEFAULT NULL, CHANGE sentiment sentiment VARCHAR(20) DEFAULT NULL, CHANGE feedbackDate feedback_date DATE NOT NULL, ADD PRIMARY KEY (feedback_id)');
        $this->addSql('ALTER TABLE session_notes CHANGE satisfactionScore `satisfactionScore` INT DEFAULT NULL, CHANGE notes `notes` LONGTEXT NOT NULL, ADD PRIMARY KEY (`noteID`)');
        $this->addSql('ALTER TABLE session_todos CHANGE id `id` INT NOT NULL, CHANGE isDone `isDone` TINYINT DEFAULT NULL, CHANGE createdAt `createdAt` DATETIME NOT NULL');
        $this->addSql('ALTER TABLE startup CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX email ON users');
        $this->addSql('ALTER TABLE users ADD face_encoding LONGTEXT DEFAULT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL, CHANGE mentor_expertise mentor_expertise LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `reclamations` CHANGE description description TEXT NOT NULL, CHANGE status status ENUM(\'OPEN\', \'IN_PROGRESS\', \'RESOLVED\', \'REJECTED\') DEFAULT \'OPEN\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE `responses` CHANGE content content TEXT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE `responses` ADD CONSTRAINT `fk_response_reclamation` FOREIGN KEY (reclamation_id) REFERENCES reclamations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `responses` ADD CONSTRAINT `fk_response_user` FOREIGN KEY (responder_user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX fk_response_reclamation ON `responses` (reclamation_id)');
        $this->addSql('CREATE INDEX fk_response_user ON `responses` (responder_user_id)');
        $this->addSql('ALTER TABLE `schedule` CHANGE `isBooked` isBooked TINYINT DEFAULT 0 NOT NULL, DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE `session` CHANGE `startupID` startupID INT DEFAULT 1 NOT NULL, CHANGE `sessionType` sessionType ENUM(\'online\', \'onsite\') DEFAULT \'online\' NOT NULL, CHANGE `status` status ENUM(\'planned\', \'completed\', \'cancelled\') DEFAULT \'planned\' NOT NULL, CHANGE `notes` notes TEXT DEFAULT NULL, CHANGE `successProbability` successProbability DOUBLE PRECISION DEFAULT \'0\', DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE `session_feedback` ADD feedbackID INT NOT NULL, ADD sessionID INT NOT NULL, ADD mentorID INT NOT NULL, ADD progressScore INT NOT NULL, ADD nextActions TEXT DEFAULT NULL, DROP feedback_id, DROP session_id, DROP mentor_id, DROP progress_score, DROP next_actions, CHANGE strengths strengths TEXT DEFAULT NULL, CHANGE weaknesses weaknesses TEXT DEFAULT NULL, CHANGE recommendations recommendations TEXT DEFAULT NULL, CHANGE sentiment sentiment VARCHAR(20) DEFAULT \'Neutral\', CHANGE feedback_date feedbackDate DATE NOT NULL, DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE `session_notes` CHANGE `satisfactionScore` satisfactionScore INT DEFAULT 5, CHANGE `notes` notes TEXT NOT NULL, DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE `session_todos` CHANGE `id` id INT AUTO_INCREMENT NOT NULL, CHANGE `isDone` isDone TINYINT DEFAULT 0, CHANGE `createdAt` createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE `startup` CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `users` DROP face_encoding, CHANGE role role ENUM(\'ENTREPRENEUR\', \'MENTOR\', \'EVALUATOR\', \'ADMIN\') NOT NULL, CHANGE status status ENUM(\'ACTIVE\', \'BLOCKED\') DEFAULT \'ACTIVE\', CHANGE mentor_expertise mentor_expertise TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX email ON `users` (email)');
    }
}
