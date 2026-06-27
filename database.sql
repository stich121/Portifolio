CREATE TABLE IF NOT EXISTS access_codes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  label VARCHAR(80) NOT NULL DEFAULT 'Principal',
  code_hash CHAR(64) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
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

INSERT INTO access_codes (label, code_hash, is_active)
SELECT 'Principal', SHA2('devstich2026', 256), 1
WHERE NOT EXISTS (SELECT 1 FROM access_codes WHERE label = 'Principal');
