<?php Alert::render('routines') ?>
<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <h4 class="fw-bold mb-0">Rotinas</h4>
    <p class="text-muted mb-2">Gerencie as rotinas cadastradas</p>
    <hr class="mt-0">
    <div class="row g-1">
        <div class="col-12 col-sm">
            <input id="search" data-table="#routines" class="form-control fs-7" type="text" placeholder="Pesquisar...">
        </div>
        <div class="col-6 col-sm-auto">
            <a href="<?= APP_BASE_URL ?>/routines/upsert" class="btn btn-primary btn-responsive" aria-label="Adicionar rotina">
                <i class="bi bi-plus-lg"></i>
                Adicionar
            </a>
        </div>
        <div class="col-6 col-sm-auto">
            <a href="<?= APP_BASE_URL ?>/routines/export" class="btn btn-export text-white btn-responsive" aria-label="Exportar rotinas">
                <i class="bi bi-download"></i>
                Exportar
            </a>
        </div>
    </div>
    <div class="table-responsive mt-3">
        <table id="routines" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Projeto</th>
                    <th>Rotina</th>
                    <th>Criador</th>
                    <?php if (Auth::canBypass()): ?>
                        <th>Empresa</th>
                    <?php endif; ?>
                    <th class="fit-column">Recorrente</th>
                    <th class="fit-column">Prioridade</th>
                </tr>
            </thead>
            <tbody class="align-middle">
                <?php if (!$routines): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            Nenhuma rotina encontrada.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($routines as $routine): ?>
                        <tr data-href="<?= APP_BASE_URL ?>/routines/upsert/<?= $routine->id ?>" class="cursor-pointer">
                            <td>
                                <?= Helper::escape($routine->project_name) ?>
                            </td>
                            <td>
                                <?= Helper::escape($routine->name) ?>
                            </td>
                            <td>
                                <?= Helper::abbreviateName($routine->creator_name) ?>
                            </td>
                            <td>
                                <?php if (Auth::canBypass()): ?>
                                    <?= Helper::abbreviateName($routine->business_name) ?>
                                <?php endif; ?>
                            </td>
                            <td class="fit-column">
                                <span class="badge <?= Helper::getMappedCssClass('badge', 'yes/no', $routine->is_recurrent) ?>">
                                    <?= Label::enum('yes/no', $routine->is_recurrent) ?>
                                </span>
                            </td>
                            <td class="fit-column">
                                <span class="badge <?= Helper::getMappedCssClass('badge', 'priority', $routine->priority) ?>">
                                    <?= Label::enum('priority', $routine->priority) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>