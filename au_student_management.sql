-- =========================
-- DATABASE
-- =========================
CREATE DATABASE IF NOT EXISTS `ufhec_academic_system`
DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `ufhec_academic_system`;

-- =========================
-- ACTIVITY LOG
-- =========================
CREATE TABLE `activity_log` (
  `activity_id` INT AUTO_INCREMENT PRIMARY KEY,
  `activity_doer` TEXT,
  `activity_text` TEXT NOT NULL,
  `activity_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================
-- ANNOUNCEMENTS
-- =========================
CREATE TABLE `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `announcement_title` TEXT,
  `announcement_content` TEXT,
  `announcement_poster_id` INT,
  `announcement_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================
-- CAREERS (ANTES STRANDS)
-- =========================
CREATE TABLE `careers` (
  `career_id` INT AUTO_INCREMENT PRIMARY KEY,
  `career_name` TEXT,
  `career_level` INT
) ENGINE=InnoDB;

INSERT INTO `careers` VALUES
(1, 'Ingeniería en Sistemas', 1),
(2, 'Administración de Empresas', 1),
(3, 'Contabilidad', 1),
(4, 'Derecho', 1),
(5, 'Educación', 1);

-- =========================
-- SUBJECTS
-- =========================
CREATE TABLE `subjects` (
  `subject_id` INT AUTO_INCREMENT PRIMARY KEY,
  `subject_name` TEXT,
  `subject_type` INT DEFAULT 0,
  `semester` INT DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO `subjects` VALUES
(1, 'Programación I', 0, 1),
(2, 'Base de Datos', 0, 2),
(3, 'Matemática I', 0, 1),
(4, 'Contabilidad I', 0, 1),
(5, 'Derecho Civil', 0, 2);

-- =========================
-- SECTIONS
-- =========================
CREATE TABLE `sections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `section_name` TEXT,
  `career_id` INT,
  `adviser_id` INT,
  `section_status` INT DEFAULT 0,
  FOREIGN KEY (`career_id`) REFERENCES `careers`(`career_id`)
  ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO `sections` VALUES
(1, 'SIS-1A', 1, 2, 1),
(2, 'ADM-1A', 2, 3, 1);

-- =========================
-- CAREER SUBJECTS
-- =========================
CREATE TABLE `career_subjects` (
  `career_subjects_id` INT AUTO_INCREMENT PRIMARY KEY,
  `career_id` INT,
  `subject_1` INT DEFAULT NULL,
  `subject_2` INT DEFAULT NULL,
  `subject_3` INT DEFAULT NULL,
  `subject_4` INT DEFAULT NULL,
  `subject_5` INT DEFAULT NULL,
  `subject_6` INT DEFAULT NULL,
  `subject_7` INT DEFAULT NULL,
  `subject_8` INT DEFAULT NULL,
  `subject_9` INT DEFAULT NULL,

  FOREIGN KEY (`career_id`) REFERENCES `careers`(`career_id`) ON DELETE CASCADE,

  FOREIGN KEY (`subject_1`) REFERENCES `subjects`(`subject_id`) ON DELETE SET NULL,
  FOREIGN KEY (`subject_2`) REFERENCES `subjects`(`subject_id`) ON DELETE SET NULL,
  FOREIGN KEY (`subject_3`) REFERENCES `subjects`(`subject_id`) ON DELETE SET NULL,
  FOREIGN KEY (`subject_4`) REFERENCES `subjects`(`subject_id`) ON DELETE SET NULL,
  FOREIGN KEY (`subject_5`) REFERENCES `subjects`(`subject_id`) ON DELETE SET NULL,
  FOREIGN KEY (`subject_6`) REFERENCES `subjects`(`subject_id`) ON DELETE SET NULL,
  FOREIGN KEY (`subject_7`) REFERENCES `subjects`(`subject_id`) ON DELETE SET NULL,
  FOREIGN KEY (`subject_8`) REFERENCES `subjects`(`subject_id`) ON DELETE SET NULL,
  FOREIGN KEY (`subject_9`) REFERENCES `subjects`(`subject_id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================
-- USER CREDENTIALS
-- =========================
CREATE TABLE `user_credentials` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) UNIQUE,
  `password` VARCHAR(255),
  `usertype` INT,
  `account_status` INT DEFAULT 1,
  `profile_status` INT DEFAULT 0
) ENGINE=InnoDB;

-- =========================
-- USER INFORMATION
-- =========================
CREATE TABLE `user_information` (
  `id` INT PRIMARY KEY,
  `firstname` TEXT,
  `middlename` TEXT,
  `lastname` TEXT,
  `gender` TEXT,
  `birthday` DATE,
  `religion` TEXT,
  `country` TEXT,
  `region` TEXT,
  `address` TEXT,
  `contact` BIGINT,
  FOREIGN KEY (`id`) REFERENCES `user_credentials`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- FAMILY BACKGROUND
-- =========================
CREATE TABLE `user_family_background` (
  `id` INT PRIMARY KEY,
  `mother_fullname` TEXT,
  `mother_work` TEXT,
  `mother_contact` BIGINT,
  `father_fullname` TEXT,
  `father_work` TEXT,
  `father_contact` BIGINT,
  `guardian_fullname` TEXT,
  `guardian_contact` BIGINT,
  `relationship` TEXT,
  FOREIGN KEY (`id`) REFERENCES `user_credentials`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- SECCION ESCOLAR
-- =========================
CREATE TABLE `user_school_info` (
  `id` INT PRIMARY KEY,
  `lrn` BIGINT,
  `section` INT,
  FOREIGN KEY (`id`) REFERENCES `user_credentials`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`section`) REFERENCES `sections`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================
-- STUDENT GRADES
-- =========================
CREATE TABLE `student_grades` (
  `grade_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT,
  `subject_id` INT,
  `grading_period_1` DOUBLE DEFAULT 0,
  `grading_period_2` DOUBLE DEFAULT 0,
  FOREIGN KEY (`student_id`) REFERENCES `user_credentials`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`subject_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================
-- TODO
-- =========================
CREATE TABLE `todo` (
  `todo_id` INT AUTO_INCREMENT PRIMARY KEY,
  `todo_owner_id` INT,
  `todo_text` TEXT,
  `todo_status` INT,
  FOREIGN KEY (`todo_owner_id`) REFERENCES `user_credentials`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

COMMIT;