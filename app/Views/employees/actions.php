<?php Alert::render('employees/actions')?>
<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4 mb-2">
    <h4 class="fw-bold mb-0">Rotina</h4>
    <p class="text-muted mb-2">Informações da rotina</p>
    <hr class="mt-0">
    <div class="d-flex flex-column mt-1 gap-3">
        <div class="row g-3">
            <?php Display::text($routine->name, 'routine_name', 'Nome', 'col-12 col-sm-4') ?>

            <?php Display::text($routine->project_name, 'project_name', 'Projeto', 'col-12 col-sm-4') ?>

            <?php Display::enum($routine->priority, 'routine_priority', 'Prioridade', 'col-12 col-sm-4', enum: 'priority') ?>
        </div>

        <div class="row g-1">
            <div class="col col-sm-auto ms-sm-auto">
                <a href="<?= APP_BASE_URL ?>/employees/routines" class="btn btn-secondary btn-responsive">Voltar</a>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <h4 class="fw-bold mb-0">Ações</h4>
    <p class="text-muted mb-2">Visualize e complete as ações da rotina selecionada</p>
    <hr class="mt-0">
    <div class="d-flex flex-column mt-1 gap-3">
        <div class="table-responsive mt-3">
            <table id="actions" class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>Ação</th>
                        <th class="fit-column">Prioridade</th>
                        <th class="fit-column">Status</th>
                        <th class="fit-column"></th>
                    </tr>
                </thead>
                <tbody class="align-middle">
                    <?php if (!$actions): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                Nenhuma ação encontrada.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($actions as $action): ?>
                            <tr>
                                <td>
                                    <?= Helper::escape($action->name) ?>
                                </td>
                                <td class="fit-column">
                                    <span class="badge <?= Helper::getMappedCssClass('badge', 'priority', $action->priority) ?>">
                                        <?= Label::enum('priority', $action->priority) ?>
                                    </span>
                                </td>
                                <td class="fit-column">
                                    <span class="badge <?= Helper::getMappedCssClass('badge', 'completion_status', $action->completion_status) ?>">
                                        <?= Label::enum('completion_status', $action->completion_status) ?>
                                    </span>
                                </td>
                                <td class="fit-column">
                                    <span class="flex-shrink-0">
                                        <?php if ($action->completion_status === 'PENDING'): ?>
                                            <a href="<?= APP_BASE_URL ?>/employees/startAction/<?= $routine->id ?>/<?= $action->id ?>" class="btn btn-secondary btn-sm" aria-label="Editar categoria">Iniciar</a>
                                        <?php elseif ($action->completion_status === 'EXECUTION'): ?>
                                            <a href="<?= APP_BASE_URL ?>/employees/endAction/<?= $routine->id ?>/<?= $action->id ?>" class="btn btn-whatsapp btn-sm" aria-label="Editar categoria">Finalizar</a>
                                        <?php endif; ?>
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