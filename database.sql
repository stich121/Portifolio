CREATE TABLE IF NOT EXISTS access_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(80) NOT NULL,
  password_hash CHAR(64) NOT NULL,
  access_level TINYINT UNSIGNED NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @access_level_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'access_users'
    AND COLUMN_NAME = 'access_level'
);
SET @access_level_sql := IF(
  @access_level_exists = 0,
  'ALTER TABLE access_users ADD COLUMN access_level TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER password_hash',
  'SELECT 1'
);
PREPARE access_level_stmt FROM @access_level_sql;
EXECUTE access_level_stmt;
DEALLOCATE PREPARE access_level_stmt;

CREATE TABLE IF NOT EXISTS presencas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(160) NOT NULL,
  company VARCHAR(160) NULL,
  status ENUM('Presente', 'Pendente', 'Ausente') NOT NULL DEFAULT 'Presente',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alunos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  teacher_id INT UNSIGNED NULL,
  name VARCHAR(160) NOT NULL,
  phone VARCHAR(40) NULL,
  class_day TINYINT UNSIGNED NOT NULL,
  class_time TIME NOT NULL,
  duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  notes VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  
  INDEX idx_teacher (teacher_id),
  INDEX idx_class_day_time (class_day, class_time),
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET @student_teacher_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'alunos'
    AND COLUMN_NAME = 'teacher_id'
);
SET @student_teacher_sql := IF(
  @student_teacher_exists = 0,
  'ALTER TABLE alunos ADD COLUMN teacher_id INT UNSIGNED NULL AFTER id, ADD INDEX idx_teacher (teacher_id)',
  'SELECT 1'
);
PREPARE student_teacher_stmt FROM @student_teacher_sql;
EXECUTE student_teacher_stmt;
DEALLOCATE PREPARE student_teacher_stmt;

UPDATE alunos
SET teacher_id = (SELECT id FROM access_users WHERE username = 'Matheus.dias' LIMIT 1)
WHERE teacher_id IS NULL;
CREATE TABLE IF NOT EXISTS chamadas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  attendance_date DATE NOT NULL,
  status ENUM('Presente', 'Pendente', 'Ausente', 'Reposicao') NOT NULL DEFAULT 'Pendente',
  replacement_date DATE NULL,
  replacement_time TIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_student_date (student_id, attendance_date),
  INDEX idx_attendance_date (attendance_date),
  CONSTRAINT fk_chamadas_student FOREIGN KEY (student_id) REFERENCES alunos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE chamadas MODIFY status ENUM('Presente', 'Pendente', 'Ausente', 'Reposicao') NOT NULL DEFAULT 'Pendente';

SET @replacement_date_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'chamadas'
    AND COLUMN_NAME = 'replacement_date'
);
SET @replacement_date_sql := IF(
  @replacement_date_exists = 0,
  'ALTER TABLE chamadas ADD COLUMN replacement_date DATE NULL AFTER status',
  'SELECT 1'
);
PREPARE replacement_date_stmt FROM @replacement_date_sql;
EXECUTE replacement_date_stmt;
DEALLOCATE PREPARE replacement_date_stmt;

SET @replacement_time_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'chamadas'
    AND COLUMN_NAME = 'replacement_time'
);
SET @replacement_time_sql := IF(
  @replacement_time_exists = 0,
  'ALTER TABLE chamadas ADD COLUMN replacement_time TIME NULL AFTER replacement_date',
  'SELECT 1'
);
PREPARE replacement_time_stmt FROM @replacement_time_sql;
EXECUTE replacement_time_stmt;
DEALLOCATE PREPARE replacement_time_stmt;


INSERT INTO access_users (username, password_hash, access_level, is_active)
SELECT 'Matheus.dias', 'ce79148500525a61846e79dddcee0cb5cc9db11f41a499fde5ae34379872ade1', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM access_users WHERE username = 'Matheus.dias');

UPDATE access_users
SET access_level = CASE
  WHEN access_level BETWEEN 1 AND 3 THEN access_level
  ELSE 1
END;

UPDATE access_users
SET access_level = 3
WHERE username = 'Matheus.dias';
-- Garante que alunos antigos fiquem vinculados ao professor principal.
UPDATE alunos
SET teacher_id = (SELECT id FROM access_users WHERE username = 'Matheus.dias' LIMIT 1)
WHERE teacher_id IS NULL;

