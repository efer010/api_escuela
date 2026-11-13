<?php
declare(strict_types=1);

use App\Core\Config;

$appName = (string) Config::get('app.name', 'Sistema Escolar');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Documentación de la API · <?= e($appName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('vendor/swagger-ui/swagger-ui.css')) ?>">
    <style>
        :root {
            --docs-header: #10233f;
            --docs-accent: #38bdf8;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #f5f7fb;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .docs-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            background: var(--docs-header);
            color: #e2e8f0;
            box-shadow: 0 2px 10px rgba(15, 23, 42, .25);
        }

        .docs-header__brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
        }

        .docs-header__mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--docs-accent), #2563eb);
            color: #fff;
            font-size: 15px;
        }

        .docs-header__title {
            font-size: 15px;
            letter-spacing: .3px;
        }

        .docs-header__subtitle {
            display: block;
            font-size: 12px;
            font-weight: 400;
            color: #94a3b8;
        }

        .docs-header__spacer {
            flex: 1;
        }

        .docs-header__link {
            padding: 7px 12px;
            border: 1px solid rgba(148, 163, 184, .45);
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 13px;
            text-decoration: none;
            transition: background .15s ease, border-color .15s ease;
        }

        .docs-header__link:hover {
            background: rgba(148, 163, 184, .16);
            border-color: var(--docs-accent);
        }

        #swagger-ui {
            max-width: 1240px;
            margin: 0 auto;
            padding: 12px 24px 60px;
        }

        .swagger-ui .topbar {
            display: none;
        }

        .swagger-ui .info {
            margin: 24px 0 28px;
        }

        .swagger-ui .info .title {
            color: #0f172a;
        }

        .swagger-ui .scheme-container {
            background: #fff;
            box-shadow: none;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
        }

        @media (max-width: 640px) {
            .docs-header {
                padding: 10px 14px;
            }

            #swagger-ui {
                padding: 8px 10px 40px;
            }

            .docs-header__subtitle {
                display: none;
            }
        }
    </style>
</head>
<body>
<header class="docs-header">
    <a class="docs-header__brand" href="<?= e(url('/')) ?>">
        <span class="docs-header__mark">SE</span>
        <span>
            <span class="docs-header__title"><?= e($appName) ?></span>
            <span class="docs-header__subtitle">Documentación interactiva de la API</span>
        </span>
    </a>
    <span class="docs-header__spacer"></span>
    <a class="docs-header__link" href="<?= e(url('/api')) ?>" target="_blank" rel="noopener">API JSON</a>
    <a class="docs-header__link" href="<?= e(url('/')) ?>">Volver al sistema</a>
</header>

<div id="swagger-ui"></div>

<script src="<?= e(asset('vendor/swagger-ui/swagger-ui-bundle.js')) ?>"></script>
<script>
    window.addEventListener('load', function () {
        window.ui = SwaggerUIBundle({
            url: <?= json_encode(url('/api/openapi.json'), JSON_UNESCAPED_SLASHES) ?>,
            dom_id: '#swagger-ui',
            layout: 'BaseLayout',
            deepLinking: true,
            filter: true,
            displayRequestDuration: true,
            tryItOutEnabled: true,
            docExpansion: 'list',
            defaultModelsExpandDepth: 1,
            defaultModelExpandDepth: 1,
            persistAuthorization: true,
            presets: [
                SwaggerUIBundle.presets.apis
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ]
        });
    });
</script>
</body>
</html>
