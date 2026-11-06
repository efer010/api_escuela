<?php
declare(strict_types=1);

$status = $status ?? 500;
$title = $title ?? 'Algo salió mal';
$message = $message ?? 'Ocurrió un error inesperado.';
$trace = $trace ?? '';
$debug = $debug ?? false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($status) ?> · <?= e($title) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="error-page">
    <main class="error-card">
        <p class="error-code"><?= e($status) ?></p>
        <h1><?= e($title) ?></h1>
        <p class="error-message"><?= e($message) ?></p>

        <div class="error-actions">
            <a class="btn btn-primary" href="<?= e(url('/')) ?>">Volver al inicio</a>
            <button class="btn btn-ghost" type="button" onclick="history.back()">Atrás</button>
        </div>

        <?php if ($debug && $trace !== ''): ?>
            <details class="error-trace">
                <summary>Detalle técnico</summary>
                <pre><?= e($trace) ?></pre>
            </details>
        <?php endif; ?>
    </main>
</body>
</html>
