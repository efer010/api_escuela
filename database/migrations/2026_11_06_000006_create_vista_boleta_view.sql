-- Migración: vista de apoyo para las boletas por alumno.
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
