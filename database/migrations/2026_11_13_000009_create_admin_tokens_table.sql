-- Migración: tokens de sesión por dispositivo.
-- Solo se almacena el hash SHA-256 del token; el valor original se entrega
-- una única vez al iniciar sesión.
CREATE TABLE IF NOT EXISTS admin_tokens (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  admin_id     INT UNSIGNED    NOT NULL,
  token_hash   CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  dispositivo  VARCHAR(120)    NOT NULL DEFAULT 'Dispositivo desconocido',
  user_agent   VARCHAR(255)    DEFAULT NULL,
  ip           VARCHAR(45)     DEFAULT NULL,
  ultimo_uso   DATETIME        DEFAULT NULL,
  expira_en    DATETIME        NOT NULL,
  revocado_en  DATETIME        DEFAULT NULL,
  creado_en    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_tokens_hash (token_hash),
  KEY idx_admin_tokens_admin (admin_id),
  KEY idx_admin_tokens_expira (expira_en),
  KEY idx_admin_tokens_revocado (revocado_en),
  CONSTRAINT fk_admin_tokens_admin
    FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
