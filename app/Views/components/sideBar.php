<button class="btn btn-dark d-lg-none position-fixed top-0 start-0 m-2 z-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar"><i class="bi bi-list"></i></button>
<div class="offcanvas offcanvas-start d-lg-flex flex-column sidebar" tabindex="-1" id="sidebar">
    <a href="<?= Helper::escape(APP_BASE_URL) ?>" class="p-4 border-bottom border-secondary-subtle d-block">
        <img src="<?= Helper::escape(APP_BASE_URL) ?>/public/img/logo_white.svg" alt="<?= Helper::escape(APP_NAME) ?>" width="211">
    </a>

    <nav class="flex-grow-1 overflow-y-auto py-2">
        <div class="menu-item" data-bs-toggle="collapse" data-bs-target="#menu-records">
            <i class="bi bi-file-earmark-text fs-6 text-white-50"></i> Cadastros<i class="bi bi-chevron-right ms-auto menu-chevron"></i>
        </div>
        <div class="collapse" id="menu-records">
            <a href="<?= Helper::escape(APP_BASE_URL) . '/users' ?>" class="submenu-item">Usuários</a>
            <?php if ($_SESSION[APP_SESSION_KEY]['user_level'] === 'MASTER'): ?> <a href="<?= Helper::escape(APP_BASE_URL) . '/businesses' ?>" class="submenu-item">Empresas</a><?php endif; ?>
            <a href="<?= Helper::escape(APP_BASE_URL) . '/routines' ?>" class="submenu-item">Rotinas</a>
        </div>
    </nav>
    <div class="px-3 py-2 text-center">
        <span class="text-white-50 small">Olá, <strong class="text-white"><?= Helper::abbreviateName($_SESSION[APP_SESSION_KEY]['user_name'] ?? 'Usuário') ?></strong>!</span>
    </div>
    <div class="p-3 border-top border-secondary-subtle">
        <a href="<?= Helper::escape(APP_BASE_URL) . '/logout' ?>" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center gap-2">
            <i class="bi bi-box-arrow-left"></i> Sair do sistema
        </a>
    </div>
</div>