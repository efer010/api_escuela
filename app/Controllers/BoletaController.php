<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\BoletaService;

final class BoletaController
{
    public function index(): void
    {
        $matricula = isset($_GET['matricula']) && is_string($_GET['matricula']) ? trim($_GET['matricula']) : null;
        $ciclo = isset($_GET['ciclo']) && is_string($_GET['ciclo']) ? trim($_GET['ciclo']) : null;

        $matricula = $matricula === '' ? null : $matricula;
        $ciclo = $ciclo === '' ? null : $ciclo;

        $rows = BoletaService::rows($matricula, $ciclo);

        View::render('boleta', [
            'title'    => 'Boletas',
            'active'   => 'boleta',
            'alumnos'  => BoletaService::alumnos(),
            'ciclos'   => BoletaService::ciclos(),
            'rows'     => $rows,
            'resumen'  => BoletaService::resumen($rows),
            'matricula' => $matricula,
            'ciclo'     => $ciclo,
        ]);
    }
}
