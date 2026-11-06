-- Migración: tabla de grupos / salones.
CREATE TABLE IF NOT EXISTS grupos (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre            VARCHAR(20)  NOT NULL,
  grado             TINYINT UNSIGNED NOT NULL,
  turno             ENUM('matutino','vespertino') NOT NULL DEFAULT 'matutino',
  aula              VARCHAR(20)  NULL,
  profesor_tutor_id INT UNSIGNED NULL,
  ciclo_escolar     VARCHAR(9)   NOT NULL,
  UNIQUE KEY uq_grupo_ciclo (nombre, ciclo_escolar),
  CONSTRAINT fk_grupo_tutor
    FOREIGN KEY (profesor_tutor_id) REFERENCES profesores(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
