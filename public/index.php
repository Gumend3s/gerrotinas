<?php
$GLOBALS['csp_nonce'] = base64_encode(random_bytes(16));
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$GLOBALS['csp_nonce']}' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; img-src 'self' data: blob:; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; connect-src 'self' https://cdn.jsdelivr.net https://viacep.com.br https://api.opencnpj.org;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'httponly' => true, 'samesite' => 'Strict',]);
session_start();

require_once dirname(__DIR__) . '/app/Settings.php';
require_once dirname(__DIR__) . '/app/ErrorHandler.php';
require_once dirname(__DIR__) . '/app/Autoloader.php';

$isLogin = str_contains(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 'login');

ob_start();
new Routes();
$pageContent = ob_get_clean();

ob_start(); ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <link rel="shortcut icon" href="<?= Helper::escape(APP_BASE_URL) ?>/public/img/favicon.ico" type="image/x-icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="<?= Helper::escape(APP_BASE_URL) ?>/public/css/styles.css?v=<?= Helper::escape(APP_VERSION) ?>">

    <title><?= Helper::escape(APP_NAME) ?></title>
</head>

<body class="d-flex flex-column min-vh-100 <?= $isLogin ? 'auth-layout' : 'app-layout' ?>">
    <?php if (!$isLogin) include APP_ROOT . '/Views/components/sideBar.php'; ?>
    <main class="flex-grow-1 pt-3<?= $isLogin ? '' : ' main-with-sidebar' ?>">
        <div class="container-fluid p-3 min-vh-100">
            <?= $pageContent ?>
        </div>
    </main>
    <footer class="border-top text-muted py-2 text-center<?= $isLogin ? '' : ' footer-with-sidebar' ?>">
        <small>
            &copy; <?= date('Y') ?> &mdash; <?= Helper::escape(APP_NAME) ?>.
            All rights reserved.
            <span class="font-monospace opacity-50 ms-1" style="font-size: 0.70rem;">v<?= Helper::escape(APP_VERSION) ?></span>
        </small>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="<?= Helper::escape(APP_BASE_URL) ?>/public/js/jquery.functions.js?v=<?= Helper::escape(APP_VERSION) ?>"></script>
    <script src="<?= Helper::escape(APP_BASE_URL) ?>/public/js/jquery.mask.js?v=<?= Helper::escape(APP_VERSION) ?>"></script>
    <script src="<?= Helper::escape(APP_BASE_URL) ?>/public/js/jquery.select.js?v=<?= Helper::escape(APP_VERSION) ?>"></script>
</body>

</html>
<?php ob_end_flush(); ?>