<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        $stats = [
            'alumnos'           => (int) Database::value('SELECT COUNT(*) FROM `alumnos`'),
            'alumnos_activos'   => (int) Database::value('SELECT COUNT(*) FROM `alumnos` WHERE `activo` = 1'),
            'profesores'        => (int) Database::value('SELECT COUNT(*) FROM `profesores`'),
            'profesores_activos' => (int) Database::value('SELECT COUNT(*) FROM `profesores` WHERE `activo` = 1'),
            'grupos'            => (int) Database::value('SELECT COUNT(*) FROM `grupos`'),
            'materias'          => (int) Database::value('SELECT COUNT(*) FROM `materias`'),
            'materias_activas'  => (int) Database::value('SELECT COUNT(*) FROM `materias` WHERE `activa` = 1'),
            'inscripciones'     => (int) Database::value('SELECT COUNT(*) FROM `inscripciones`'),
            'promedio'          => Database::value('SELECT AVG(`calificacion`) FROM `inscripciones` WHERE `calificacion` IS NOT NULL'),
        ];

        $ultimosAlumnos = Database::all(
            'SELECT a.`id`, a.`matricula`, a.`nombre`, a.`apellido_paterno`, a.`apellido_materno`,
                    a.`activo`, g.`nombre` AS grupo_nombre, g.`ciclo_escolar` AS grupo_ciclo
             FROM `alumnos` a
             LEFT JOIN `grupos` g ON g.`id` = a.`grupo_id`
             ORDER BY a.`creado_en` DESC, a.`id` DESC
             LIMIT 6'
        );

        $ultimasInscripciones = Database::all(
            "SELECT i.`id`, i.`calificacion`, i.`estado`, i.`ciclo_escolar`,
                    CONCAT(a.`matricula`, ' · ', a.`apellido_paterno`, ' ', a.`nombre`) AS alumno_nombre,
                    m.`clave` AS materia_clave, m.`nombre` AS materia_nombre
             FROM `inscripciones` i
             JOIN `alumnos` a ON a.`id` = i.`alumno_id`
             JOIN `materias` m ON m.`id` = i.`materia_id`
             ORDER BY i.`id` DESC
             LIMIT 6"
        );

        $porGrupo = Database::all(
            'SELECT g.`id`, g.`nombre`, g.`grado`, g.`turno`, g.`ciclo_escolar`, COUNT(a.`id`) AS total
             FROM `grupos` g
             LEFT JOIN `alumnos` a ON a.`grupo_id` = g.`id`
             GROUP BY g.`id`, g.`nombre`, g.`grado`, g.`turno`, g.`ciclo_escolar`
             ORDER BY g.`ciclo_escolar` DESC, g.`grado` ASC, g.`nombre` ASC
             LIMIT 8'
        );

        View::render('dashboard', [
            'title'                => 'Inicio',
            'active'               => 'dashboard',
            'stats'                => $stats,
            'ultimosAlumnos'       => $ultimosAlumnos,
            'ultimasInscripciones' => $ultimasInscripciones,
            'porGrupo'             => $porGrupo,
        ]);
    }
}
