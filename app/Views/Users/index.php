<?php Alert::render('users') ?>
<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <h4 class="fw-bold mb-0">Usuários</h4>
    <p class="text-muted mb-2">Gerencie os usuários do sistema</p>
    <hr class="mt-0">
    <div class="row g-1">
        <div class="col-12 col-sm">
            <input id="search" data-table="#users" class="form-control fs-7" type="text" placeholder="Pesquisar...">
        </div>
        <div class="col col-sm-auto">
            <a href="<?= APP_BASE_URL ?>/users/upsert" class="btn btn-primary btn-responsive" aria-label="Adicionar usuário">
                <i class="bi bi-plus-lg"></i>
                Adicionar
            </a>
        </div>
        <div class="col col-sm-auto">
            <a href="<?= APP_BASE_URL ?>/users/export" class="btn btn-export text-white btn-responsive" aria-label="Exportar usuários">
                <i class="bi bi-download"></i>
                Exportar
            </a>
        </div>
    </div>
    <div class="table-responsive mt-3">
        <table id="users" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th class="d-none d-sm-table-cell">E-mail</th>
                    <th class="d-none d-md-table-cell">Empresa</th>
                    <th class="d-none d-md-table-cell text-center" style="width: 110px">Permissão</th>
                </tr>
            </thead>
            <tbody class="align-middle">
                <?php if (!$users): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            Nenhum usuário encontrado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr data-href="<?= APP_BASE_URL ?>/users/upsert/<?= $user->id ?>" class="cursor-pointer"
                            data-search="<?= Helper::escape($user->name) ?>">
                            <td>
                                <?= Helper::abbreviateName($user->name) ?>
                                <div class="d-md-none small mt-1 text-muted">
                                    <?= Helper::escape($user->business_name) ?>
                                    ·
                                    <span class="<?= Helper::getMappedCssClass('text', 'level', $user->level) ?>">
                                        <?= Label::enum('level', $user->level) ?>
                                    </span>
                                </div>
                            </td>
                            <td class="d-none d-sm-table-cell">
                                <?= Helper::escape($user->email) ?>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <?= Helper::escape($user->business_name) ?>
                            </td>
                            <td class="d-none d-md-table-cell text-center">
                                <span class="badge rounded-pill <?= Helper::getMappedCssClass('badge', 'level', $user->level) ?>" style="width: 90px">
                                    <?= Label::enum('level', $user->level) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>