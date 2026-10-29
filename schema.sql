-- LMS Quiz database schema (MySQL / MariaDB)
-- Reconstructed from the queries used in the application code.
-- Usage: create a database (for example `lms_quiz`), then import this file in phpMyAdmin.
-- Note: the tables `attempts` and `draft` are also created automatically by config.php if missing.

CREATE TABLE IF NOT EXISTS users (
  id         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nim        VARCHAR(30)  NOT NULL,
  nama       VARCHAR(120) NOT NULL,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('mhs','admin') NOT NULL DEFAULT 'mhs',
  email      VARCHAR(120) NULL,
  hp         VARCHAR(25)  NULL,
  prodi      VARCHAR(10)  NULL,
  last_login DATETIME     NULL,
  UNIQUE KEY uq_users_nim (nim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quizzes (
  id         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  judul      VARCHAR(200) NOT NULL,
  durasi     INT NOT NULL DEFAULT 30,           -- minutes
  aktif      TINYINT NOT NULL DEFAULT 1,
  pengampu   VARCHAR(120) NULL,                 -- lecturer name
  pembahasan TINYINT NOT NULL DEFAULT 0         -- 1 = answer review is open
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questions (
  id      INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  quiz_id INT NOT NULL,
  soal    TEXT NOT NULL,
  a       TEXT NULL,
  b       TEXT NULL,
  c       TEXT NULL,
  d       TEXT NULL,
  e       TEXT NULL,
  kunci   CHAR(1) NOT NULL,                     -- correct option: A to E
  KEY idx_questions_quiz (quiz_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS results (
  id           INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  quiz_id      INT NOT NULL,
  skor         INT NOT NULL DEFAULT 0,
  benar        INT NOT NULL DEFAULT 0,
  total        INT NOT NULL DEFAULT 0,
  durasi_detik INT NULL,
  jawaban      MEDIUMTEXT NULL,                 -- JSON of submitted answers
  waktu        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_results_user_quiz (user_id, quiz_id),
  KEY idx_results_quiz (quiz_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attempts (
  user_id  INT NOT NULL,
  quiz_id  INT NOT NULL,
  mulai    DATETIME NULL,
  terakhir DATETIME NULL,
  PRIMARY KEY (user_id, quiz_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS draft (
  user_id INT NOT NULL,
  quiz_id INT NOT NULL,
  jawab   MEDIUMTEXT NULL,
  diubah  DATETIME NULL,
  PRIMARY KEY (user_id, quiz_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
