<?php Alert::render('index') ?>
<div class="px-4">
    <div class="mb-5">
        <p class="home-label text-uppercase fw-semibold mb-3">Sistema GER Rotinas</p>
        <h1 class="display-5 fw-light mb-4"><strong class="fw-bold d-block"></strong></h1>
        <p class="home-desc">Sistema de gerenciamento de rotinas</p>
    </div>
    <div class="mb-5">
        <div class="fs-7 fc-tertiary text-uppercase fw-semibold mb-3">Módulos</div>
        <div class="row g-0 rounded overflow-hidden border">
            <?php foreach ($modules as $i => $module): ?>
                <div class="col home-module d-flex flex-column align-items-center gap-3 bg-white <?= $i > 0 ? 'border-start' : '' ?>">
                    <div class="home-module-icon-wrap d-flex align-items-center justify-content-center">
                        <i class="bi <?= $module['icon'] ?>"></i>
                    </div>
                    <span class="home-module-name"><?= Helper::escape($module['name']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="fs-7 fc-tertiary text-uppercase fw-semibold mb-3">Diferenciais</div>
    <div class="row g-4">
        <?php foreach ($features as [$title, $desc]): ?>
            <div class="col-12 col-md-6 col-xl-4 d-flex gap-3">
                <div class="feature-line flex-shrink-0"></div>
                <div>
                    <h3 class="fs-6 fw-semibold mb-1"><?= Helper::escape($title) ?></h3>
                    <p class="text-muted small mb-0"><?= Helper::escape($desc) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>