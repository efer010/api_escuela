-- Migración: tabla de alumnos.
CREATE TABLE IF NOT EXISTS alumnos (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  matricula         VARCHAR(20)  NOT NULL UNIQUE,
  nombre            VARCHAR(80)  NOT NULL,
  apellido_paterno  VARCHAR(80)  NOT NULL,
  apellido_materno  VARCHAR(80)  NULL,
  fecha_nacimiento  DATE         NOT NULL,
  sexo              ENUM('F','M','X') NULL,
  correo            VARCHAR(120) NULL UNIQUE,
  telefono          VARCHAR(20)  NULL,
  direccion         VARCHAR(200) NULL,
  grupo_id          INT UNSIGNED NULL,
  fecha_inscripcion DATE         NOT NULL DEFAULT (CURRENT_DATE),
  activo            TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_alumno_grupo
    FOREIGN KEY (grupo_id) REFERENCES grupos(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_alumno_nombre (apellido_paterno, apellido_materno, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
