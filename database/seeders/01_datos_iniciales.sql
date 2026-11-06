-- Datos de ejemplo idempotentes.
-- Se ejecutan con: php bin/migrate.php seed
-- INSERT IGNORE evita duplicados si los registros ya existen.

INSERT IGNORE INTO profesores
  (id, numero_empleado, nombre, apellido_paterno, apellido_materno, correo, especialidad, fecha_contratacion)
VALUES
  (1, 'EMP-001', 'Laura',  'García', 'Ruiz',  'laura.garcia@escuela.mx',  'Matemáticas', '2020-08-15'),
  (2, 'EMP-002', 'Carlos', 'Méndez', 'López', 'carlos.mendez@escuela.mx', 'Ciencias',    '2019-01-10');

INSERT IGNORE INTO grupos
  (id, nombre, grado, turno, aula, profesor_tutor_id, ciclo_escolar)
VALUES
  (1, '3-A', 3, 'matutino', 'A-201', 1, '2025-2026');

INSERT IGNORE INTO alumnos
  (id, matricula, nombre, apellido_paterno, apellido_materno, fecha_nacimiento, sexo, correo, grupo_id)
VALUES
  (1, 'A-2025-001', 'Ana',   'Pérez',   'Soto', '2010-03-21', 'F', 'ana.perez@alumno.escuela.mx',     1),
  (2, 'A-2025-002', 'Diego', 'Ramírez', 'Cruz', '2010-07-09', 'M', 'diego.ramirez@alumno.escuela.mx', 1);

INSERT IGNORE INTO materias
  (id, clave, nombre, descripcion, creditos, horas_semana, profesor_id)
VALUES
  (1, 'MAT-301', 'Matemáticas III', 'Álgebra y geometría analítica', 8, 5, 1),
  (2, 'CIE-301', 'Ciencias III',    'Física y química básica',       8, 5, 2);

INSERT IGNORE INTO inscripciones
  (id, alumno_id, materia_id, ciclo_escolar, calificacion, estado)
VALUES
  (1, 1, 1, '2025-2026', 9.50, 'aprobado'),
  (2, 1, 2, '2025-2026', 8.75, 'aprobado'),
  (3, 2, 1, '2025-2026', 7.00, 'inscrito');
