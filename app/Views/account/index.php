<?php
declare(strict_types=1);

$admin = $admin ?? [];
$tokens = $tokens ?? [];
$currentTokenId = $currentTokenId ?? null;
$activeTokens = $activeTokens ?? 0;
$passwordErrors = $passwordErrors ?? [];
$passwordMin = $passwordMin ?? 8;

$currentToken = null;
foreach ($tokens as $token) {
    if ((int) $token['id'] === (int) $currentTokenId) {
        $currentToken = $token;
        break;
    }
}

$statusLabel = static function (string $status): array {
    return match ($status) {
        'activa'   => ['Activa', 'success'],
        'expirada' => ['Expirada', 'muted'],
        'revocada' => ['Revocada', 'danger'],
        default    => [$status, 'muted'],
    };
};
?>

<section class="page-head">
    <div>
        <p class="page-head__eyebrow">Seguridad de la cuenta</p>
        <h2>Mi cuenta</h2>
    </div>
</section>

<div class="account-grid">
    <section class="card">
        <header class="card__header">
            <div>
                <h2>Perfil del administrador</h2>
                <p>Datos de la cuenta con la que iniciaste sesión.</p>
            </div>
        </header>

        <dl class="detail-grid account-details">
            <div class="detail-item">
                <dt>Nombre</dt>
                <dd><?= e($admin['nombre'] ?? '—') ?></dd>
            </div>
            <div class="detail-item">
                <dt>Usuario</dt>
                <dd><code><?= e($admin['usuario'] ?? '—') ?></code></dd>
            </div>
            <div class="detail-item">
                <dt>Correo</dt>
                <dd><?= e($admin['correo'] ?? '—') ?></dd>
            </div>
            <div class="detail-item">
                <dt>Último acceso</dt>
                <dd><?= e(format_datetime($admin['ultimo_acceso'] ?? null)) ?></dd>
            </div>
        </dl>

        <?php if ($currentToken !== null): ?>
            <div class="account-device-note">
                <span class="account-device-note__icon" aria-hidden="true">💻</span>
                <div>
                    <strong>Este dispositivo</strong>
                    <p>
                        <?= e($currentToken['dispositivo'] ?? 'Dispositivo desconocido') ?>
                        · IP <?= e($currentToken['ip'] ?? '—') ?>
                        · expira el <?= e(format_datetime($currentToken['expira_en'] ?? null)) ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <header class="card__header">
            <div>
                <h2>Cambiar contraseña</h2>
                <p>Al cambiarla se cerrarán las sesiones de los demás dispositivos.</p>
            </div>
        </header>

        <form class="form-grid account-password-form" method="post" action="<?= e(url('/cuenta/password')) ?>">
            <?= csrf_field() ?>

            <label class="field field--wide <?= isset($passwordErrors['current_password']) ? 'field--has-error' : '' ?>">
                <span>Contraseña actual</span>
                <input type="password" name="current_password" autocomplete="current-password" required>
                <?php if (isset($passwordErrors['current_password'])): ?>
                    <span class="field__error"><?= e($passwordErrors['current_password']) ?></span>
                <?php endif; ?>
            </label>

            <label class="field <?= isset($passwordErrors['new_password']) ? 'field--has-error' : '' ?>">
                <span>Nueva contraseña</span>
                <input type="password" name="new_password" autocomplete="new-password"
                       minlength="<?= e((string) $passwordMin) ?>" required>
                <?php if (isset($passwordErrors['new_password'])): ?>
                    <span class="field__error"><?= e($passwordErrors['new_password']) ?></span>
                <?php else: ?>
                    <span class="field__help">Mínimo <?= e((string) $passwordMin) ?> caracteres.</span>
                <?php endif; ?>
            </label>

            <label class="field <?= isset($passwordErrors['new_password_confirm']) ? 'field--has-error' : '' ?>">
                <span>Confirmar contraseña</span>
                <input type="password" name="new_password_confirm" autocomplete="new-password"
                       minlength="<?= e((string) $passwordMin) ?>" required>
                <?php if (isset($passwordErrors['new_password_confirm'])): ?>
                    <span class="field__error"><?= e($passwordErrors['new_password_confirm']) ?></span>
                <?php endif; ?>
            </label>

            <div class="form-actions field--wide">
                <button class="btn btn-primary" type="submit">Actualizar contraseña</button>
            </div>
        </form>
    </section>
</div>

<section class="card">
    <header class="card__header card__header--split">
        <div>
            <h2>Dispositivos con acceso</h2>
            <p>
                Cada inicio de sesión genera un token independiente.
                Tienes <strong><?= e((string) $activeTokens) ?></strong> dispositivo(s) con sesión activa.
            </p>
        </div>

        <?php if ($activeTokens > 1): ?>
            <form method="post" action="<?= e(url('/cuenta/dispositivos/revocar-otras')) ?>"
                  data-confirm="¿Cerrar la sesión en todos los demás dispositivos?">
                <?= csrf_field() ?>
                <button class="btn btn-danger btn-sm" type="submit">Revocar otros dispositivos</button>
            </form>
        <?php endif; ?>
    </header>

    <?php if ($tokens === []): ?>
        <div class="empty-state">
            <span class="empty-state__icon">🔐</span>
            <h3>Sin dispositivos registrados</h3>
            <p>Los dispositivos aparecerán aquí cuando inicien sesión.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Dispositivo</th>
                        <th>IP</th>
                        <th>Creado</th>
                        <th>Último uso</th>
                        <th>Expira</th>
                        <th>Estado</th>
                        <th class="table__actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tokens as $token): ?>
                        <?php
                        $isCurrent = (int) $token['id'] === (int) $currentTokenId;
                        [$label, $tone] = $statusLabel((string) ($token['estado'] ?? ''));
                        ?>
                        <tr class="<?= $isCurrent ? 'is-current' : '' ?>">
                            <td>
                                <strong><?= e($token['dispositivo'] ?? 'Dispositivo desconocido') ?></strong>
                                <?php if ($isCurrent): ?>
                                    <span class="badge badge-info">Este dispositivo</span>
                                <?php endif; ?>
                                <?php if (!empty($token['user_agent'])): ?>
                                    <small class="table__hint" title="<?= e($token['user_agent']) ?>">
                                        <?= e(mb_strimwidth((string) $token['user_agent'], 0, 70, '…')) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><code><?= e($token['ip'] ?? '—') ?></code></td>
                            <td><?= e(format_datetime($token['creado_en'] ?? null)) ?></td>
                            <td><?= e(format_datetime($token['ultimo_uso'] ?? null)) ?></td>
                            <td><?= e(format_datetime($token['expira_en'] ?? null)) ?></td>
                            <td><span class="badge badge-<?= e($tone) ?>"><?= e($label) ?></span></td>
                            <td class="table__actions">
                                <?php if (($token['estado'] ?? '') === 'activa' && !$isCurrent): ?>
                                    <form method="post"
                                          action="<?= e(url('/cuenta/dispositivos/' . (int) $token['id'] . '/revocar')) ?>"
                                          data-confirm="¿Revocar el acceso de este dispositivo?">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-danger btn-xs" type="submit">Revocar</button>
                                    </form>
                                <?php elseif ($isCurrent): ?>
                                    <form method="post" action="<?= e(url('/logout')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-secondary btn-xs" type="submit">Cerrar sesión</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <footer class="account-footer">
        <p>
            <strong>Seguridad:</strong> el sistema solo guarda un hash SHA-256 de cada token.
            El token original nunca se almacena ni se muestra de nuevo.
        </p>
    </footer>
</section>
