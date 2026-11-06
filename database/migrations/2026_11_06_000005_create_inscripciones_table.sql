-- Migración: tabla de inscripciones (relación alumno <-> materia).
CREATE TABLE IF NOT EXISTS inscripciones (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  alumno_id         INT UNSIGNED NOT NULL,
  materia_id        INT UNSIGNED NOT NULL,
  ciclo_escolar     VARCHAR(9)   NOT NULL,
  calificacion      DECIMAL(4,2) NULL CHECK (calificacion BETWEEN 0 AND 10),
  fecha_inscripcion DATE         NOT NULL DEFAULT (CURRENT_DATE),
  estado            ENUM('inscrito','aprobado','reprobado','baja')
                      NOT NULL DEFAULT 'inscrito',
  UNIQUE KEY uq_inscripcion (alumno_id, materia_id, ciclo_escolar),
  CONSTRAINT fk_inscripcion_alumno
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_inscripcion_materia
    FOREIGN KEY (materia_id) REFERENCES materias(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
