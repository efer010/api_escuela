<?php
declare(strict_types=1);

/*
 * Definición declarativa de los recursos CRUD.
 * Estas reglas alimentan formularios, validación, tablas, filtros y la API.
 */

return [
    'alumnos' => [
        'table'        => 'alumnos',
        'primary_key'  => 'id',
        'singular'     => 'Alumno',
        'plural'       => 'Alumnos',
        'icon'         => '🎓',
        'description'  => 'Estudiantes inscritos en la institución.',
        'default_order' => ['apellido_paterno' => 'ASC', 'apellido_materno' => 'ASC', 'nombre' => 'ASC'],
        'search'       => ['matricula', 'nombre', 'apellido_paterno', 'apellido_materno', 'correo'],
        'sortable'     => ['matricula', 'apellido_paterno', 'fecha_inscripcion', 'activo'],
        'list'         => ['matricula', 'nombre_completo', 'grupo', 'correo', 'activo'],
        'filters'      => ['grupo_id', 'activo'],
        'fields'       => [
            'matricula' => [
                'label' => 'Matrícula', 'type' => 'text', 'rules' => ['required', 'max:20'],
                'unique' => true, 'placeholder' => 'A-2025-001',
            ],
            'nombre' => [
                'label' => 'Nombre(s)', 'type' => 'text', 'rules' => ['required', 'max:80'],
            ],
            'apellido_paterno' => [
                'label' => 'Apellido paterno', 'type' => 'text', 'rules' => ['required', 'max:80'],
            ],
            'apellido_materno' => [
                'label' => 'Apellido materno', 'type' => 'text', 'rules' => ['max:80'],
            ],
            'fecha_nacimiento' => [
                'label' => 'Fecha de nacimiento', 'type' => 'date', 'rules' => ['required', 'date'],
            ],
            'sexo' => [
                'label' => 'Sexo', 'type' => 'enum',
                'options' => ['F' => 'Femenino', 'M' => 'Masculino', 'X' => 'Otro'],
                'rules' => ['in:F,M,X'],
            ],
            'correo' => [
                'label' => 'Correo electrónico', 'type' => 'email', 'rules' => ['email', 'max:120'],
                'unique' => true, 'placeholder' => 'alumno@escuela.mx',
            ],
            'telefono' => [
                'label' => 'Teléfono', 'type' => 'tel', 'rules' => ['max:20'],
            ],
            'direccion' => [
                'label' => 'Dirección', 'type' => 'textarea', 'rules' => ['max:200'], 'wide' => true, 'rows' => 2,
            ],
            'grupo_id' => [
                'label' => 'Grupo', 'type' => 'select', 'rules' => ['exists:grupos,id'], 'cast' => 'int',
                'empty' => 'Sin grupo asignado',
                'relation' => [
                    'table' => 'grupos', 'value' => 'id',
                    'label' => "CONCAT(`nombre`, ' · ', `ciclo_escolar`, ' (', `turno`, ')')",
                ],
            ],
            'fecha_inscripcion' => [
                'label' => 'Fecha de inscripción', 'type' => 'date', 'rules' => ['required', 'date'],
                'default' => 'today',
            ],
            'activo' => [
                'label' => 'Activo', 'type' => 'bool', 'default' => 1,
            ],
        ],
        'virtual' => [
            'nombre_completo' => ['label' => 'Nombre completo', 'compute' => 'nombre_completo'],
            'grupo'           => ['label' => 'Grupo', 'relation_field' => 'grupo_id', 'empty' => 'Sin grupo'],
        ],
        'unique_groups' => [],
    ],

    'profesores' => [
        'table'        => 'profesores',
        'primary_key'  => 'id',
        'singular'     => 'Profesor',
        'plural'       => 'Profesores',
        'icon'         => '👩‍🏫',
        'description'  => 'Personal docente y tutores de grupo.',
        'default_order' => ['apellido_paterno' => 'ASC', 'apellido_materno' => 'ASC', 'nombre' => 'ASC'],
        'search'       => ['numero_empleado', 'nombre', 'apellido_paterno', 'apellido_materno', 'correo', 'especialidad'],
        'sortable'     => ['numero_empleado', 'apellido_paterno', 'fecha_contratacion', 'activo'],
        'list'         => ['numero_empleado', 'nombre_completo', 'especialidad', 'correo', 'activo'],
        'filters'      => ['activo'],
        'fields'       => [
            'numero_empleado' => [
                'label' => 'Número de empleado', 'type' => 'text', 'rules' => ['required', 'max:20'],
                'unique' => true, 'placeholder' => 'EMP-001',
            ],
            'nombre' => [
                'label' => 'Nombre(s)', 'type' => 'text', 'rules' => ['required', 'max:80'],
            ],
            'apellido_paterno' => [
                'label' => 'Apellido paterno', 'type' => 'text', 'rules' => ['required', 'max:80'],
            ],
            'apellido_materno' => [
                'label' => 'Apellido materno', 'type' => 'text', 'rules' => ['max:80'],
            ],
            'correo' => [
                'label' => 'Correo electrónico', 'type' => 'email', 'rules' => ['required', 'email', 'max:120'],
                'unique' => true, 'placeholder' => 'profesor@escuela.mx',
            ],
            'telefono' => [
                'label' => 'Teléfono', 'type' => 'tel', 'rules' => ['max:20'],
            ],
            'especialidad' => [
                'label' => 'Especialidad', 'type' => 'text', 'rules' => ['max:100'],
                'placeholder' => 'Matemáticas, Ciencias, etc.',
            ],
            'fecha_contratacion' => [
                'label' => 'Fecha de contratación', 'type' => 'date', 'rules' => ['required', 'date'],
            ],
            'activo' => [
                'label' => 'Activo', 'type' => 'bool', 'default' => 1,
            ],
        ],
        'virtual' => [
            'nombre_completo' => ['label' => 'Nombre completo', 'compute' => 'nombre_completo'],
        ],
        'unique_groups' => [],
    ],

    'grupos' => [
        'table'        => 'grupos',
        'primary_key'  => 'id',
        'singular'     => 'Grupo',
        'plural'       => 'Grupos',
        'icon'         => '🏫',
        'description'  => 'Grupos y salones por ciclo escolar.',
        'default_order' => ['ciclo_escolar' => 'DESC', 'grado' => 'ASC', 'nombre' => 'ASC'],
        'search'       => ['nombre', 'ciclo_escolar', 'aula'],
        'sortable'     => ['nombre', 'grado', 'turno', 'ciclo_escolar'],
        'list'         => ['nombre', 'grado', 'turno', 'aula', 'tutor', 'ciclo_escolar'],
        'filters'      => ['turno'],
        'fields'       => [
            'nombre' => [
                'label' => 'Nombre del grupo', 'type' => 'text', 'rules' => ['required', 'max:20'],
                'placeholder' => '3-A',
            ],
            'grado' => [
                'label' => 'Grado', 'type' => 'number', 'rules' => ['required', 'int', 'between:1,6'],
                'cast' => 'int', 'min' => 1, 'max' => 6, 'default' => 1,
            ],
            'turno' => [
                'label' => 'Turno', 'type' => 'enum',
                'options' => ['matutino' => 'Matutino', 'vespertino' => 'Vespertino'],
                'rules' => ['required', 'in:matutino,vespertino'], 'default' => 'matutino',
            ],
            'aula' => [
                'label' => 'Aula', 'type' => 'text', 'rules' => ['max:20'], 'placeholder' => 'A-201',
            ],
            'profesor_tutor_id' => [
                'label' => 'Profesor tutor', 'type' => 'select', 'rules' => ['exists:profesores,id'],
                'cast' => 'int', 'empty' => 'Sin tutor asignado',
                'relation' => [
                    'table' => 'profesores', 'value' => 'id',
                    'label' => "CONCAT(`numero_empleado`, ' · ', `nombre`, ' ', `apellido_paterno`)",
                ],
            ],
            'ciclo_escolar' => [
                'label' => 'Ciclo escolar', 'type' => 'text', 'rules' => ['required', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
                'placeholder' => '2025-2026', 'help' => 'Formato AAAA-AAAA, por ejemplo 2025-2026.',
            ],
        ],
        'virtual' => [
            'tutor' => ['label' => 'Tutor', 'relation_field' => 'profesor_tutor_id', 'empty' => 'Sin tutor'],
        ],
        'unique_groups' => [['nombre', 'ciclo_escolar']],
    ],

    'materias' => [
        'table'        => 'materias',
        'primary_key'  => 'id',
        'singular'     => 'Materia',
        'plural'       => 'Materias',
        'icon'         => '📚',
        'description'  => 'Catálogo de materias y asignaturas.',
        'default_order' => ['clave' => 'ASC'],
        'search'       => ['clave', 'nombre', 'descripcion'],
        'sortable'     => ['clave', 'nombre', 'creditos', 'horas_semana', 'activa'],
        'list'         => ['clave', 'nombre', 'creditos', 'horas_semana', 'profesor', 'activa'],
        'filters'      => ['profesor_id', 'activa'],
        'fields'       => [
            'clave' => [
                'label' => 'Clave', 'type' => 'text', 'rules' => ['required', 'max:20'],
                'unique' => true, 'placeholder' => 'MAT-301',
            ],
            'nombre' => [
                'label' => 'Nombre de la materia', 'type' => 'text', 'rules' => ['required', 'max:100'],
            ],
            'descripcion' => [
                'label' => 'Descripción', 'type' => 'textarea', 'rules' => ['max:255'], 'wide' => true, 'rows' => 3,
            ],
            'creditos' => [
                'label' => 'Créditos', 'type' => 'number', 'rules' => ['required', 'int', 'between:0,255'],
                'cast' => 'int', 'min' => 0, 'max' => 255, 'default' => 0,
            ],
            'horas_semana' => [
                'label' => 'Horas por semana', 'type' => 'number', 'rules' => ['required', 'int', 'between:0,255'],
                'cast' => 'int', 'min' => 0, 'max' => 255, 'default' => 0,
            ],
            'profesor_id' => [
                'label' => 'Profesor', 'type' => 'select', 'rules' => ['exists:profesores,id'],
                'cast' => 'int', 'empty' => 'Sin profesor asignado',
                'relation' => [
                    'table' => 'profesores', 'value' => 'id',
                    'label' => "CONCAT(`numero_empleado`, ' · ', `nombre`, ' ', `apellido_paterno`)",
                ],
            ],
            'activa' => [
                'label' => 'Activa', 'type' => 'bool', 'default' => 1,
            ],
        ],
        'virtual' => [
            'profesor' => ['label' => 'Profesor', 'relation_field' => 'profesor_id', 'empty' => 'Sin profesor'],
        ],
        'unique_groups' => [],
    ],

    'inscripciones' => [
        'table'        => 'inscripciones',
        'primary_key'  => 'id',
        'singular'     => 'Inscripción',
        'plural'       => 'Inscripciones',
        'icon'         => '📝',
        'description'  => 'Relación entre alumnos y materias.',
        'default_order' => ['id' => 'DESC'],
        'search'       => ['ciclo_escolar'],
        'sortable'     => ['id', 'ciclo_escolar', 'calificacion', 'estado', 'fecha_inscripcion'],
        'list'         => ['alumno', 'materia', 'ciclo_escolar', 'calificacion', 'estado', 'fecha_inscripcion'],
        'filters'      => ['estado', 'ciclo_escolar'],
        'fields'       => [
            'alumno_id' => [
                'label' => 'Alumno', 'type' => 'select', 'rules' => ['required', 'exists:alumnos,id'],
                'cast' => 'int', 'empty' => 'Selecciona un alumno',
                'relation' => [
                    'table' => 'alumnos', 'value' => 'id',
                    'label' => "CONCAT(`matricula`, ' · ', `apellido_paterno`, ' ', `nombre`)",
                ],
            ],
            'materia_id' => [
                'label' => 'Materia', 'type' => 'select', 'rules' => ['required', 'exists:materias,id'],
                'cast' => 'int', 'empty' => 'Selecciona una materia',
                'relation' => [
                    'table' => 'materias', 'value' => 'id',
                    'label' => "CONCAT(`clave`, ' · ', `nombre`)",
                ],
            ],
            'ciclo_escolar' => [
                'label' => 'Ciclo escolar', 'type' => 'text', 'rules' => ['required', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
                'placeholder' => '2025-2026', 'help' => 'Formato AAAA-AAAA.',
            ],
            'calificacion' => [
                'label' => 'Calificación', 'type' => 'number', 'rules' => ['numeric', 'between:0,10'],
                'cast' => 'float', 'step' => '0.01', 'min' => 0, 'max' => 10, 'help' => 'Déjala vacía si aún no está calificada.',
            ],
            'fecha_inscripcion' => [
                'label' => 'Fecha de inscripción', 'type' => 'date', 'rules' => ['required', 'date'],
                'default' => 'today',
            ],
            'estado' => [
                'label' => 'Estado', 'type' => 'enum',
                'options' => ['inscrito' => 'Inscrito', 'aprobado' => 'Aprobado', 'reprobado' => 'Reprobado', 'baja' => 'Baja'],
                'rules' => ['required', 'in:inscrito,aprobado,reprobado,baja'], 'default' => 'inscrito',
            ],
        ],
        'virtual' => [
            'alumno'  => ['label' => 'Alumno', 'relation_field' => 'alumno_id', 'empty' => 'Sin alumno'],
            'materia' => ['label' => 'Materia', 'relation_field' => 'materia_id', 'empty' => 'Sin materia'],
        ],
        'unique_groups' => [['alumno_id', 'materia_id', 'ciclo_escolar']],
    ],
];
