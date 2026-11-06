-- Migración: tabla histórica alumnos_anterior (legado).
-- Se conserva para poder reconstruir la base completa; el CRUD no la utiliza.
CREATE TABLE IF NOT EXISTS alumnos_anterior (
  id_alumno INT NOT NULL AUTO_INCREMENT,
  Nombre    VARCHAR(50) DEFAULT NULL,
  Apellidos VARCHAR(50) DEFAULT NULL,
  Matricula VARCHAR(15) DEFAULT NULL,
  Semestre  INT         DEFAULT 1,
  PRIMARY KEY (id_alumno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
