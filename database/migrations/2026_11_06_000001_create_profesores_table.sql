-- Migración: tabla de profesores.
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
