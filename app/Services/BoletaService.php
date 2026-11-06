<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class BoletaService
{
    /**
     * Alumnos disponibles para el selector de boleta.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function alumnos(): array
    {
        return Database::all(
            "SELECT `matricula` AS value,
                    CONCAT(`matricula`, ' · ', `apellido_paterno`, ' ',
                           COALESCE(`apellido_materno`, ''), ', ', `nombre`) AS label
             FROM `alumnos`
             ORDER BY `apellido_paterno` ASC, `apellido_materno` ASC, `nombre` ASC"
        );
    }

    /**
     * Ciclos escolares existentes.
     *
     * @return array<int, string>
     */
    public static function ciclos(): array
    {
        $rows = Database::all(
            "SELECT `ciclo_escolar` FROM `grupos`
             UNION
             SELECT `ciclo_escolar` FROM `inscripciones`
             ORDER BY `ciclo_escolar` DESC"
        );

        return array_values(array_filter(array_map(
            static fn (array $row): string => (string) $row['ciclo_escolar'],
            $rows
        )));
    }

    public static function rows(?string $matricula = null, ?string $ciclo = null): array
    {
        $sql = 'SELECT * FROM `vista_boleta` WHERE 1 = 1';
        $params = [];

        if ($matricula !== null && $matricula !== '') {
            $sql .= ' AND `matricula` = ?';
            $params[] = $matricula;
        }

        if ($ciclo !== null && $ciclo !== '') {
            $sql .= ' AND `ciclo_escolar` = ?';
            $params[] = $ciclo;
        }

        $sql .= ' ORDER BY `alumno` ASC, `materia` ASC';

        return Database::all($sql, $params);
    }

    public static function resumen(array $rows): array
    {
        $suma = 0.0;
        $calificadas = 0;
        $aprobadas = 0;
        $reprobadas = 0;
        $inscritas = 0;
        $bajas = 0;

        foreach ($rows as $row) {
            $calificacion = $row['calificacion'] ?? null;
            if ($calificacion !== null && $calificacion !== '') {
                $suma += (float) $calificacion;
                $calificadas++;
            }

            match ($row['estado'] ?? '') {
                'aprobado'  => $aprobadas++,
                'reprobado' => $reprobadas++,
                'inscrito'  => $inscritas++,
                'baja'      => $bajas++,
                default     => null,
            };
        }

        return [
            'total'       => count($rows),
            'calificadas' => $calificadas,
            'promedio'    => $calificadas > 0 ? round($suma / $calificadas, 2) : null,
            'aprobadas'   => $aprobadas,
            'reprobadas'  => $reprobadas,
            'inscritas'   => $inscritas,
            'bajas'       => $bajas,
        ];
    }
}
