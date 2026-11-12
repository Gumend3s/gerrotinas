<?php Alert::render('assignedRoutines');
$showActions = [
    'back_to_actions',
];
require APP_ROOT . "/Views/routines/components/summary.php";
?>
<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <div class="row g-1">
        <div class="col-12 col-sm-auto">
            <h4 class="fw-bold mb-0">Delegações</h4>
            <p class="text-muted mb-2">Preencha os dados para delegar essa rotina</p>
        </div>
    </div>
    <hr class="mt-0">
    <form name="projectForm" method="POST" action="<?= APP_BASE_URL ?>/assignedRoutines/assign/<?= $routine_id ?>" novalidate class="row g-1">
        <?php
        Input::select($data, 'employee_id', 'Funcionário', $employees, 'col-12 col-sm');

        Input::numeric($data, 'start_date', 'Data início', 'Ex: 00/00/0000', 'col-12 col-sm-3', classes: 'date');

        Input::numeric($data, 'end_date', 'Data Limite', 'Ex: 00/00/0000', 'col-12 col-sm-3', classes: 'date');
        ?>

        <div class="col col-sm-auto">
            <label class="invisible d-none d-sm-block">.</label>
            <div class="row g-1">
                <div class="col col-sm-auto">
                    <button type="submit" class="btn btn-primary btn-responsive">
                        Delegar
                    </button>
                </div>
            </div>
        </div>
    </form>
    <p class="text-muted mb-2 mt-4">Gerencie as delegações da rotina selecionada</p>
    <hr class="mt-0">
    <div class="d-flex flex-column flex-sm-row gap-1">
        <input id="search" data-table="#assign" class="form-control fs-7" type="text" placeholder="Pesquisar...">
        <div class="col col-sm-auto">
            <a href="<?= APP_BASE_URL ?>/assignedRoutines/export/<?= $routine_id ?>" class="btn btn-export text-white btn-responsive" aria-label="Exportar atribuições">
                <i class="bi bi-download"></i>
                Exportar
            </a>
        </div>
    </div>
    <div class="table-responsive mt-3">
        <table id="assign" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Delegador</th>
                    <th>Funcionário</th>
                    <th>Período</th>
                    <th>Status</th>
                    <th class="fit-column"></th>
                </tr>
            </thead>
            <tbody class="align-middle">
                <?php if (!$assigned_routines): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Nenhuma atribuição encontrada.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assigned_routines as $assigned_routine): ?>
                        <tr>
                            <td>
                                <?= Helper::escape($assigned_routine->assigner_name) ?>
                            </td>
                            <td>
                                <?= Helper::escape($assigned_routine->employee_name) ?>
                            </td>
                            <td>
                                <?= Helper::convertDateFormat($assigned_routine->start_date) ?> - <?= Helper::convertDateFormat($assigned_routine->end_date) ?>
                            </td>
                            <td>
                                <?= Label::enum('completion_status', $assigned_routine->completion_status) ?>
                            </td>
                            <td class="fit-column">
                                <?php if ($assigned_routine->completion_status !== 'CANCELED'): ?>
                                    <span
                                    class="btn btn-sm btn-danger confirm-action"
                                    data-href="<?= APP_BASE_URL ?>/assignedRoutines/cancel/<?= $routine_id  ?>/<?= $assigned_routine->id ?>"
                                    data-title="Quer mesmo cancelar essa delegação?"
                                    data-html='Essa ação é irreversível.'
                                    data-confirm-text="Sim, cancelar"
                                    data-icon="warning">
                                    <i class="bi bi-ban"></i>
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>