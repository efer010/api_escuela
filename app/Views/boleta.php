<?php
declare(strict_types=1);

use App\Core\Config;

$needsRouteField = !Config::get('app.pretty_urls', true);
$estadoClasses = ['inscrito' => 'info', 'aprobado' => 'success', 'reprobado' => 'danger', 'baja' => 'muted'];
?>

<section class="page-head">
    <div>
        <p class="page-head__eyebrow">Consulta de calificaciones por alumno y ciclo escolar</p>
        <h2>Boletas</h2>
    </div>
    <button class="btn btn-ghost no-print" type="button" data-print>🖨️ Imprimir</button>
</section>

<section class="card no-print">
    <form class="toolbar" method="get" action="<?= e(url('/boleta')) ?>">
        <?php if ($needsRouteField): ?>
            <input type="hidden" name="r" value="boleta">
        <?php endif; ?>

        <label class="filter filter--grow">
            <span>Alumno</span>
            <select name="matricula">
                <option value="">Todos los alumnos</option>
                <?php foreach ($alumnos as $alumno): ?>
                    <option value="<?= e((string) $alumno['value']) ?>"
                        <?= (string) $alumno['value'] === (string) $matricula ? 'selected' : '' ?>>
                        <?= e((string) $alumno['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="filter">
            <span>Ciclo escolar</span>
            <select name="ciclo">
                <option value="">Todos los ciclos</option>
                <?php foreach ($ciclos as $cicloOption): ?>
                    <option value="<?= e($cicloOption) ?>" <?= $cicloOption === (string) $ciclo ? 'selected' : '' ?>>
                        <?= e($cicloOption) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="toolbar__actions">
            <button class="btn btn-secondary btn-sm" type="submit">Consultar</button>
            <a class="btn btn-ghost btn-sm" href="<?= e(url('/boleta')) ?>">Limpiar</a>
        </div>
    </form>
</section>

<section class="summary-strip">
    <div class="summary-chip">
        <span>Registros</span>
        <strong><?= e((string) $resumen['total']) ?></strong>
    </div>
    <div class="summary-chip">
        <span>Promedio</span>
        <strong><?= $resumen['promedio'] !== null ? e(number_format((float) $resumen['promedio'], 2)) : '—' ?></strong>
    </div>
    <div class="summary-chip summary-chip--success">
        <span>Aprobadas</span>
        <strong><?= e((string) $resumen['aprobadas']) ?></strong>
    </div>
    <div class="summary-chip summary-chip--danger">
        <span>Reprobadas</span>
        <strong><?= e((string) $resumen['reprobadas']) ?></strong>
    </div>
    <div class="summary-chip summary-chip--info">
        <span>En curso</span>
        <strong><?= e((string) $resumen['inscritas']) ?></strong>
    </div>
</section>

<section class="card">
    <?php if ($rows === []): ?>
        <div class="empty-state">
            <span class="empty-state__icon">🧾</span>
            <h3>Sin resultados</h3>
            <p>No hay inscripciones que coincidan con los filtros seleccionados.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table table--boleta">
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Alumno</th>
                        <th>Ciclo</th>
                        <th>Clave</th>
                        <th>Materia</th>
                        <th class="text-right">Calificación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><span class="mono"><?= e($row['matricula']) ?></span></td>
                            <td><?= e($row['alumno']) ?></td>
                            <td><?= e($row['ciclo_escolar']) ?></td>
                            <td><span class="mono text-muted"><?= e($row['clave_materia']) ?></span></td>
                            <td><?= e($row['materia']) ?></td>
                            <td class="text-right">
                                <?php if ($row['calificacion'] === null): ?>
                                    <span class="text-muted">—</span>
                                <?php else: ?>
                                    <strong><?= e(number_format((float) $row['calificacion'], 2)) ?></strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $estado = (string) $row['estado']; ?>
                                <span class="badge badge-<?= e($estadoClasses[$estado] ?? 'muted') ?>">
                                    <?= e(ucfirst($estado)) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
