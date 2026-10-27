<?php Alert::render('actions');
$showActions = [
    'go_back',
    'go_to_upsert',
];
require APP_ROOT . "/Views/routines/components/summary.php";
?>

<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <h4 class="fw-bold mb-0">Ações</h4>
    <p class="text-muted mb-2">Gerencie as ações cadastradas da rotina</p>
    <hr class="mt-0">
    <div class="row g-1">
        <div class="col-12 col-sm">
            <input id="search" data-table="#actions" class="form-control fs-7" type="text" placeholder="Pesquisar...">
        </div>
        <div class="col-6 col-sm-auto">
            <a href="<?= APP_BASE_URL ?>/actions/upsert/<?= $routine_id ?>" class="btn btn-primary btn-responsive" aria-label="Adicionar ação">
                <i class="bi bi-plus-lg"></i>
                Adicionar
            </a>
        </div>
        <div class="col-6 col-sm-auto">
            <a href="<?= APP_BASE_URL ?>/actions/export/<?= $routine_id ?>" class="btn btn-export text-white btn-responsive" aria-label="Exportar ações">
                <i class="bi bi-download"></i>
                Exportar
            </a>
        </div>
    </div>
    <div class="d-flex flex-column mt-1 gap-3">
        <div class="table-responsive mt-3">
            <table id="actions" class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>Ação</th>
                        <th>Criador</th>
                        <th class="fit-column">Prioridade</th>
                    </tr>
                </thead>
                <tbody class="align-middle">
                    <?php if (!$actions): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                Nenhuma ação encontrada.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($actions as $action): ?>
                            <tr data-href="<?= APP_BASE_URL ?>/actions/upsert/<?= $routine_id ?>/<?= $action->id ?>" class="cursor-pointer">
                                <td>
                                    <?= Helper::escape($action->name) ?>
                                </td>
                                <td>
                                    <?= Helper::abbreviateName($action->creator_name) ?>
                                </td>
                                <td class="fit-column">
                                    <span class="badge <?= Helper::getMappedCssClass('badge', 'priority', $action->priority) ?>">
                                        <?= Label::enum('priority', $action->priority) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>