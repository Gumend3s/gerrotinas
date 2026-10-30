<?php Alert::render('businesses') ?>
<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <h4 class="fw-bold mb-0">Empresas</h4>
    <p class="text-muted mb-2">Gerencie as empresas cadastrados</p>
    <hr class="mt-0">
    <div class="row g-1">
        <div class="col-12 col-sm">
            <input id="search" data-table="#businesses" class="form-control fs-7" type="text" placeholder="Pesquisar...">
        </div>
        <div class="col col-sm-auto">
            <a href="<?= APP_BASE_URL ?>/businesses/upsert" class="btn btn-primary btn-responsive" aria-label="Adicionar empresa">
                <i class="bi bi-plus-lg"></i>
                Adicionar
            </a>
        </div>
        <div class="col col-sm-auto">
            <a href="<?= APP_BASE_URL ?>/businesses/export" class="btn btn-export text-white btn-responsive" aria-label="Exportar empresas">
                <i class="bi bi-download"></i>
                Exportar
            </a>
        </div>
    </div>
    <div class="table-responsive mt-3">
        <table id="businesses" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th class="d-none d-md-table-cell">CNPJ</th>
                </tr>
            </thead>
            <tbody class="align-middle">
                <?php if (!$businesses): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">
                            Nenhum empresa encontrada.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($businesses as $business): ?>
                        <tr data-href="<?= APP_BASE_URL ?>/businesses/upsert/<?= $business->id ?>" class="cursor-pointer"
                            data-search="<?= Helper::escape($business->legal_name) ?>">
                            <td>
                                <?= Helper::escape($business->trade_name) ?>
                                <div class="d-md-none small mt-1 text-muted">
                                    <?= Helper::escape($business->cnpj) ?>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <?= Helper::escape($business->cnpj) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>