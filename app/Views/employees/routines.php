<?php Alert::render('employees/routines') ?>
<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <h4 class="fw-bold mb-0">Rotinas</h4>
    <p class="text-muted mb-2">Visualize e acesse as rotinas atribuídas a você</p>
    <hr class="mt-0">
    <div class="table-responsive mt-3">
        <table id="routines" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Projeto</th>
                    <th>Rotina</th>
                    <th>Delegador</th>
                    <th>Data Limite</th>
                    <th class="fit-column">Prioridade</th>
                    <th class="fit-column">Status</th>
                </tr>
            </thead>
            <tbody class="align-middle">
                <?php if (!$routines): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Nenhuma rotina encontrada.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($routines as $routine): ?>
                        <tr data-href="<?= APP_BASE_URL ?>/employees/actions/<?= $routine->id ?>" class="cursor-pointer">
                            <td>
                                <?= Helper::escape($routine->project_name) ?>
                            </td>
                            <td>
                                <?= Helper::escape($routine->name) ?>
                            </td>
                            <td>
                                <?= Helper::abbreviateName($routine->assigner_name) ?>
                            </td>
                            <td>
                                <?= Helper::convertDateFormat($routine->end_date) ?>
                            </td>
                            <td class="fit-column">
                                <span class="badge <?= Helper::getMappedCssClass('badge', 'priority', $routine->priority) ?>">
                                    <?= Label::enum('priority', $routine->priority) ?>
                                </span>
                            </td>
                            <td class="fit-column">
                                <span class="badge <?= Helper::getMappedCssClass('badge', 'completion_status', $routine->completion_status) ?>">
                                    <?= Label::enum('completion_status', $routine->completion_status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>