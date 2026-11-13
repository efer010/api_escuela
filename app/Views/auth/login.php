<?php
declare(strict_types=1);

use App\Core\Config;

$appName = (string) Config::get('app.name', 'Sistema Escolar');
$error = $error ?? null;
$notice = $notice ?? null;
$next = $next ?? '/';
$login = $login ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · <?= e($appName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="auth-page">
<main class="auth-shell">
    <section class="auth-card" aria-labelledby="login-title">
        <div class="auth-brand">
            <span class="auth-brand__mark">SE</span>
            <span class="auth-brand__text">
                <strong><?= e($appName) ?></strong>
                <small>Administración escolar</small>
            </span>
        </div>

        <h1 id="login-title">Iniciar sesión</h1>
        <p class="auth-subtitle">Acceso exclusivo para administradores del sistema.</p>

        <?php if ($notice !== null): ?>
            <div class="alert alert--info" role="status">
                <span class="alert__icon">i</span>
                <span><?= e($notice) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert--error" role="alert">
                <span class="alert__icon">!</span>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <form class="auth-form" method="post" action="<?= e(url('/login')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">

            <label class="field">
                <span>Usuario o correo</span>
                <input type="text" name="login" value="<?= e($login) ?>"
                       autocomplete="username" maxlength="150" required autofocus>
            </label>

            <label class="field">
                <span>Contraseña</span>
                <input type="password" name="password" autocomplete="current-password"
                       minlength="1" required>
            </label>

            <button class="btn btn-primary auth-submit" type="submit">Entrar al sistema</button>
        </form>

        <p class="auth-help">
            ¿No tienes acceso? Un administrador puede crear tu cuenta desde la consola:
            <code>php bin/admin.php create &lt;usuario&gt;</code>
        </p>
    </section>

    <p class="auth-footer">
        <span class="dot"></span> PHP <?= e(PHP_VERSION) ?> · MariaDB · <?= e($appName) ?>
    </p>
</main>
</body>
</html>
