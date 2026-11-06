<?php
declare(strict_types=1);

$promedio = $stats['promedio'] !== null ? number_format((float) $stats['promedio'], 2) : '—';
$hoy = (new DateTimeImmutable('now'))->format('d/m/Y');
?>

<section class="hero">
    <div>
        <p class="hero__eyebrow">Resumen general · <?= e($hoy) ?></p>
        <h2>Bienvenido al sistema escolar</h2>
        <p class="hero__text">
            Administra alumnos, profesores, grupos, materias e inscripciones desde un solo lugar.
            Todas las operaciones están disponibles también mediante la API JSON.
        </p>
        <div class="hero__actions">
            <a class="btn btn-primary" href="<?= e(url('/alumnos/nuevo')) ?>">+ Nuevo alumno</a>
            <a class="btn btn-ghost" href="<?= e(url('/boleta')) ?>">Consultar boletas</a>
        </div>
    </div>
    <div class="hero__metric">
        <span class="hero__metric-label">Promedio general</span>
        <strong><?= e($promedio) ?></strong>
        <span class="hero__metric-hint">sobre 10 · <?= e((string) $stats['inscripciones']) ?> inscripciones</span>
    </div>
</section>

<section class="stats-grid">
    <a class="stat-card" href="<?= e(url('/alumnos')) ?>">
        <span class="stat-card__icon">🎓</span>
        <span class="stat-card__label">Alumnos</span>
        <strong class="stat-card__value"><?= e((string) $stats['alumnos']) ?></strong>
        <span class="stat-card__hint"><?= e((string) $stats['alumnos_activos']) ?> activos</span>
    </a>
    <a class="stat-card" href="<?= e(url('/profesores')) ?>">
        <span class="stat-card__icon">👩‍🏫</span>
        <span class="stat-card__label">Profesores</span>
        <strong class="stat-card__value"><?= e((string) $stats['profesores']) ?></strong>
        <span class="stat-card__hint"><?= e((string) $stats['profesores_activos']) ?> activos</span>
    </a>
    <a class="stat-card" href="<?= e(url('/grupos')) ?>">
        <span class="stat-card__icon">🏫</span>
        <span class="stat-card__label">Grupos</span>
        <strong class="stat-card__value"><?= e((string) $stats['grupos']) ?></strong>
        <span class="stat-card__hint">por ciclo escolar</span>
    </a>
    <a class="stat-card" href="<?= e(url('/materias')) ?>">
        <span class="stat-card__icon">📚</span>
        <span class="stat-card__label">Materias</span>
        <strong class="stat-card__value"><?= e((string) $stats['materias']) ?></strong>
        <span class="stat-card__hint"><?= e((string) $stats['materias_activas']) ?> activas</span>
    </a>
    <a class="stat-card" href="<?= e(url('/inscripciones')) ?>">
        <span class="stat-card__icon">📝</span>
        <span class="stat-card__label">Inscripciones</span>
        <strong class="stat-card__value"><?= e((string) $stats['inscripciones']) ?></strong>
        <span class="stat-card__hint">registros totales</span>
    </a>
</section>

<div class="grid-2">
    <section class="card">
        <header class="card__header">
            <div>
                <h2>Últimos alumnos</h2>
                <p>Registros más recientes</p>
            </div>
            <a class="link" href="<?= e(url('/alumnos')) ?>">Ver todos</a>
        </header>

        <?php if ($ultimosAlumnos === []): ?>
            <p class="empty-state">Aún no hay alumnos registrados.</p>
        <?php else: ?>
            <ul class="simple-list">
                <?php foreach ($ultimosAlumnos as $alumno): ?>
                    <li>
                        <a class="avatar" href="<?= e(url('/alumnos/' . $alumno['id'])) ?>">
                            <?= e(mb_strtoupper(mb_substr((string) $alumno['nombre'], 0, 1))) ?>
                        </a>
                        <div class="simple-list__body">
                            <a href="<?= e(url('/alumnos/' . $alumno['id'])) ?>">
                                <?= e(nombre_completo($alumno)) ?>
                            </a>
                            <span><?= e($alumno['matricula']) ?>
                                <?php if (!empty($alumno['grupo_nombre'])): ?>
                                    · Grupo <?= e($alumno['grupo_nombre']) ?> (<?= e($alumno['grupo_ciclo']) ?>)
                                <?php endif; ?>
                            </span>
                        </div>
                        <?= cell_html(['fields' => ['activo' => ['type' => 'bool']], 'virtual' => []], $alumno, 'activo') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="card">
        <header class="card__header">
            <div>
                <h2>Alumnos por grupo</h2>
                <p>Distribución actual</p>
            </div>
            <a class="link" href="<?= e(url('/grupos')) ?>">Grupos</a>
        </header>

        <?php if ($porGrupo === []): ?>
            <p class="empty-state">Aún no hay grupos registrados.</p>
        <?php else: ?>
            <ul class="bar-list">
                <?php
                $maximo = max(array_map(static fn (array $grupo): int => (int) $grupo['total'], $porGrupo));
                foreach ($porGrupo as $grupo):
                    $total = (int) $grupo['total'];
                    $porcentaje = $maximo > 0 ? max(4, (int) round(($total / $maximo) * 100)) : 4;
                ?>
                    <li>
                        <div class="bar-list__row">
                            <span><?= e($grupo['nombre']) ?> · <?= e($grupo['ciclo_escolar']) ?></span>
                            <strong><?= e((string) $total) ?></strong>
                        </div>
                        <div class="bar"><span style="width: <?= e((string) $porcentaje) ?>%"></span></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<section class="card">
    <header class="card__header">
        <div>
            <h2>Últimas inscripciones</h2>
            <p>Movimientos recientes en materias</p>
        </div>
        <a class="link" href="<?= e(url('/inscripciones')) ?>">Ver todas</a>
    </header>

    <?php if ($ultimasInscripciones === []): ?>
        <p class="empty-state">Aún no hay inscripciones registradas.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Alumno</th>
                        <th>Materia</th>
                        <th>Ciclo</th>
                        <th>Calificación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimasInscripciones as $inscripcion): ?>
                        <tr>
                            <td><?= e($inscripcion['alumno_nombre']) ?></td>
                            <td>
                                <span class="text-muted"><?= e($inscripcion['materia_clave']) ?></span>
                                <?= e($inscripcion['materia_nombre']) ?>
                            </td>
                            <td><?= e($inscripcion['ciclo_escolar']) ?></td>
                            <td>
                                <?php if ($inscripcion['calificacion'] === null): ?>
                                    <span class="text-muted">Sin calificar</span>
                                <?php else: ?>
                                    <strong><?= e(number_format((float) $inscripcion['calificacion'], 2)) ?></strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $estado = (string) $inscripcion['estado'];
                                $clases = ['inscrito' => 'info', 'aprobado' => 'success', 'reprobado' => 'danger', 'baja' => 'muted'];
                                ?>
                                <span class="badge badge-<?= e($clases[$estado] ?? 'muted') ?>"><?= e(ucfirst($estado)) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
