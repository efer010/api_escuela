<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Resources;
use App\Services\Auth;

$appName = (string) Config::get('app.name', 'Sistema Escolar');
$currentActive = $active ?? '';
$navItems = Resources::all();
$currentAdmin = Auth::user();
$adminName = (string) ($currentAdmin['nombre'] ?? 'Administrador');
$adminUser = (string) ($currentAdmin['usuario'] ?? '');
$initials = '';
foreach (preg_split('/\s+/', trim($adminName)) ?: [] as $part) {
    if ($part === '') {
        continue;
    }
    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    if (mb_strlen($initials) >= 2) {
        break;
    }
}
$initials = $initials === '' ? 'A' : $initials;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? $appName) ?> · <?= e($appName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="app-shell">
    <div class="sidebar-overlay" data-sidebar-close></div>

    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?= e(url('/')) ?>">
            <span class="brand__mark">SE</span>
            <span class="brand__text">
                <strong><?= e($appName) ?></strong>
                <small>Base de datos <code>escuela</code></small>
            </span>
        </a>

        <nav class="nav" aria-label="Navegación principal">
            <a class="nav__link <?= $currentActive === 'dashboard' ? 'is-active' : '' ?>" href="<?= e(url('/')) ?>">
                <span class="nav__icon">🏠</span><span>Inicio</span>
            </a>

            <p class="nav__label">Catálogos</p>
            <?php foreach ($navItems as $resourceName => $resourceDefinition): ?>
                <a class="nav__link <?= $currentActive === $resourceName ? 'is-active' : '' ?>"
                   href="<?= e(url('/' . $resourceName)) ?>">
                    <span class="nav__icon"><?= e($resourceDefinition['icon'] ?? '•') ?></span>
                    <span><?= e($resourceDefinition['plural']) ?></span>
                </a>
            <?php endforeach; ?>

            <p class="nav__label">Consultas</p>
            <a class="nav__link <?= $currentActive === 'boleta' ? 'is-active' : '' ?>" href="<?= e(url('/boleta')) ?>">
                <span class="nav__icon">🧾</span><span>Boletas</span>
            </a>
            <a class="nav__link" href="<?= e(url('/api')) ?>" target="_blank" rel="noopener">
                <span class="nav__icon">🔌</span><span>API JSON</span>
            </a>
            <a class="nav__link" href="<?= e(url('/api/docs')) ?>" target="_blank" rel="noopener">
                <span class="nav__icon">📘</span><span>API Docs</span>
            </a>

            <p class="nav__label">Cuenta</p>
            <a class="nav__link <?= $currentActive === 'cuenta' ? 'is-active' : '' ?>" href="<?= e(url('/cuenta')) ?>">
                <span class="nav__icon">👤</span><span>Mi cuenta</span>
            </a>
            <form class="nav__logout" method="post" action="<?= e(url('/logout')) ?>">
                <?= csrf_field() ?>
                <button class="nav__link nav__link--button" type="submit">
                    <span class="nav__icon">⏻</span><span>Cerrar sesión</span>
                </button>
            </form>
        </nav>

        <div class="sidebar__footer">
            <span class="dot"></span> PHP <?= e(PHP_VERSION) ?> · MariaDB
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="icon-button menu-button" type="button" data-sidebar-toggle aria-label="Abrir menú">☰</button>
            <div class="topbar__title">
                <span class="topbar__eyebrow"><?= e($appName) ?></span>
                <h1><?= e($title ?? 'Inicio') ?></h1>
            </div>
            <div class="topbar__actions">
                <a class="btn btn-ghost btn-sm topbar__api" href="<?= e(url('/api')) ?>" target="_blank" rel="noopener">API</a>
                <a class="topbar__user" href="<?= e(url('/cuenta')) ?>" title="Mi cuenta">
                    <span class="topbar__avatar"><?= e($initials) ?></span>
                    <span class="topbar__user-text">
                        <strong><?= e($adminName) ?></strong>
                        <small><?= e($adminUser) ?></small>
                    </span>
                </a>
                <form method="post" action="<?= e(url('/logout')) ?>" class="topbar__logout">
                    <?= csrf_field() ?>
                    <button class="btn btn-secondary btn-sm" type="submit">Salir</button>
                </form>
            </div>
        </header>

        <main class="content" id="main-content">
            <?= flash_messages() ?>
            <?= $content ?>
        </main>
    </div>
</div>

<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
