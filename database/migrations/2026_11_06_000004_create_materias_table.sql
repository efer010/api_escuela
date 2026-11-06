-- Migración: tabla de materias.
CREATE TABLE IF NOT EXISTS materias (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  clave         VARCHAR(20)  NOT NULL UNIQUE,
  nombre        VARCHAR(100) NOT NULL,
  descripcion   VARCHAR(255) NULL,
  creditos      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  horas_semana  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  profesor_id   INT UNSIGNED NULL,
  activa        TINYINT(1)   NOT NULL DEFAULT 1,
  CONSTRAINT fk_materia_profesor
    FOREIGN KEY (profesor_id) REFERENCES profesores(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
