-- ============================================================
--  Base de datos: escuela
--  Motor: MySQL / MariaDB 8+
--  Ejecutar con:  mysql -u root -p < escuela.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS escuela
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_spanish_ci;

USE escuela;

-- ------------------------------------------------------------
--  Profesores
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS profesores (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero_empleado    VARCHAR(20)  NOT NULL UNIQUE,
  nombre             VARCHAR(80)  NOT NULL,
  apellido_paterno   VARCHAR(80)  NOT NULL,
  apellido_materno   VARCHAR(80)  NULL,
  correo             VARCHAR(120) NOT NULL UNIQUE,
  telefono           VARCHAR(20)  NULL,
  especialidad       VARCHAR(100) NULL,
  fecha_contratacion DATE         NOT NULL,
  activo             TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  Grupos / salones
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS grupos (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre           VARCHAR(20)  NOT NULL,          -- ej. "3-A"
  grado            TINYINT UNSIGNED NOT NULL,      -- 1 a 6
  turno            ENUM('matutino','vespertino') NOT NULL DEFAULT 'matutino',
  aula             VARCHAR(20)  NULL,
  profesor_tutor_id INT UNSIGNED NULL,
  ciclo_escolar    VARCHAR(9)   NOT NULL,          -- ej. "2025-2026"
  UNIQUE KEY uq_grupo_ciclo (nombre, ciclo_escolar),
  CONSTRAINT fk_grupo_tutor
    FOREIGN KEY (profesor_tutor_id) REFERENCES profesores(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  Alumnos
-- ------------------------------------------------------------
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
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  Materias
-- ------------------------------------------------------------
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
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  Inscripciones (relación alumno <-> materia)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS inscripciones (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  alumno_id       INT UNSIGNED NOT NULL,
  materia_id      INT UNSIGNED NOT NULL,
  ciclo_escolar   VARCHAR(9)   NOT NULL,
  calificacion    DECIMAL(4,2) NULL CHECK (calificacion BETWEEN 0 AND 10),
  fecha_inscripcion DATE       NOT NULL DEFAULT (CURRENT_DATE),
  estado          ENUM('inscrito','aprobado','reprobado','baja')
                    NOT NULL DEFAULT 'inscrito',
  UNIQUE KEY uq_inscripcion (alumno_id, materia_id, ciclo_escolar),
  CONSTRAINT fk_inscripcion_alumno
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_inscripcion_materia
    FOREIGN KEY (materia_id) REFERENCES materias(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
--  Vista de apoyo: boleta por alumno
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW vista_boleta AS
SELECT
  a.matricula,
  CONCAT(a.apellido_paterno, ' ', COALESCE(a.apellido_materno, ''), ', ', a.nombre) AS alumno,
  i.ciclo_escolar,
  m.clave   AS clave_materia,
  m.nombre  AS materia,
  i.calificacion,
  i.estado
FROM inscripciones i
JOIN alumnos  a ON a.id = i.alumno_id
JOIN materias m ON m.id = i.materia_id;

-- ------------------------------------------------------------
--  Datos de ejemplo (opcionales)
-- ------------------------------------------------------------
INSERT INTO profesores
  (numero_empleado, nombre, apellido_paterno, apellido_materno, correo, especialidad, fecha_contratacion)
VALUES
  ('EMP-001', 'Laura',  'García',  'Ruiz',    'laura.garcia@escuela.mx',  'Matemáticas', '2020-08-15'),
  ('EMP-002', 'Carlos', 'Méndez',  'López',   'carlos.mendez@escuela.mx', 'Ciencias',    '2019-01-10');

INSERT INTO grupos (nombre, grado, turno, aula, profesor_tutor_id, ciclo_escolar)
VALUES ('3-A', 3, 'matutino', 'A-201', 1, '2025-2026');

INSERT INTO alumnos
  (matricula, nombre, apellido_paterno, apellido_materno, fecha_nacimiento, sexo, correo, grupo_id)
VALUES
  ('A-2025-001', 'Ana',   'Pérez',  'Soto',   '2010-03-21', 'F', 'ana.perez@alumno.escuela.mx', 1),
  ('A-2025-002', 'Diego', 'Ramírez','Cruz',   '2010-07-09', 'M', 'diego.ramirez@alumno.escuela.mx', 1);

INSERT INTO materias (clave, nombre, descripcion, creditos, horas_semana, profesor_id)
VALUES
  ('MAT-301', 'Matemáticas III', 'Álgebra y geometría analítica', 8, 5, 1),
  ('CIE-301', 'Ciencias III',    'Física y química básica',       8, 5, 2);

INSERT INTO inscripciones (alumno_id, materia_id, ciclo_escolar, calificacion, estado)
VALUES
  (1, 1, '2025-2026', 9.50, 'aprobado'),
  (1, 2, '2025-2026', 8.75, 'aprobado'),
  (2, 1, '2025-2026', 7.00, 'inscrito');
