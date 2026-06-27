CREATE TABLE IF NOT EXISTS access_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(80) NOT NULL,
  password_hash CHAR(64) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  name VARCHAR(160) NOT NULL,
  phone VARCHAR(40) NULL,
  class_day TINYINT UNSIGNED NOT NULL,
  class_time TIME NOT NULL,
  duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  notes VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_class_day_time (class_day, class_time),
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS chamadas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  attendance_date DATE NOT NULL,
  status ENUM('Presente', 'Pendente', 'Ausente') NOT NULL DEFAULT 'Pendente',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_student_date (student_id, attendance_date),
  INDEX idx_attendance_date (attendance_date),
  CONSTRAINT fk_chamadas_student FOREIGN KEY (student_id) REFERENCES alunos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO access_users (username, password_hash, is_active)
SELECT 'Matheus.dias', 'ce79148500525a61846e79dddcee0cb5cc9db11f41a499fde5ae34379872ade1', 1
WHERE NOT EXISTS (SELECT 1 FROM access_users WHERE username = 'Matheus.dias');

