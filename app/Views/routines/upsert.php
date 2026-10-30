<?php Alert::render('routines/upsert') ?>
<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <h4 class="fw-bold mb-0"><?= $isEditing ? 'Editar' : 'Cadastrar' ?> rotina</h4>
    <p class="text-muted mb-2">
        <?= $isEditing
            ? 'Atualize as informações da rotina selecionada'
            : 'Preencha os dados para cadastrar uma nova rotina' ?>
    </p>
    <hr class="mt-0">
    <form name="routineForm" method="POST" action="<?= APP_BASE_URL ?>/routines/upsert<?= $isEditing ? "/$id" : '' ?>" novalidate class="d-flex flex-column mt-1 gap-3">

        <div class="row g-3">
            <?php Input::text($data, 'name', 'Nome', 'Ex: ...', 'col-12 col-sm-6') ?>

            <?php Input::select($data, 'project_id', 'Projeto', $projects, 'col-12 col-sm-3', buttons: [
                [
                    'class' => 'btn-primary rounded-end',
                    'href' => APP_BASE_URL . '/projects' . ($id != '' ? "?routine_id=$id" : ''),
                    'aria-label' => 'Adicionar projeto',
                    'icon' => 'bi-plus-lg'
                ]
            ]) ?>

            <?php Input::select($data, 'priority', 'Prioridade', $priorities, 'col-12 col-sm-3') ?>

            <?php Input::select($data, 'is_recurrent', 'Recorrente', [true => 'Sim', false => 'Não'], 'col-12 col-sm-4', required: false, classes: 'is-recurrent') ?>

            <?php
            $isRecurrent = $data['is_recurrent'];

            Display::text(null, '', 'Recorrência', 'col-12 col-sm-4 recurrence-display' . ($isRecurrent ? ' d-none' : ''));

            Display::text(null, '', 'Período', 'col-12 col-sm-4 recurrence-display' . ($isRecurrent ? ' d-none' : ''));
            
            Input::select($data, 'recurrence_rule', 'Recorrência', $recurrence_rules, 'col-12 col-sm-4 recurrence-input' . ($isRecurrent ? '' : ' d-none'), required: false);

            Input::text($data, 'recurrence_value', 'Período', 'Ex: 2', 'col-12 col-sm-4 recurrence-input' . ($isRecurrent ? '' : ' d-none'), required: false, classes: 'number-2');
            ?>
        </div>

        <div class="row g-1">
            <?php if ($isEditing): ?>
                <div class="col col-sm-auto">
                    <span
                        class="btn btn-danger confirm-action btn-responsive"
                        data-href="<?= APP_BASE_URL ?>/routines/delete/<?= $id ?>"
                        data-title="Quer mesmo deletar essa rotina?"
                        data-html='Essa ação é irreversível.'
                        data-confirm-text="Sim, deletar"
                        data-icon="warning">
                        Deletar
                    </span>
                </div>
            <?php endif; ?>

            <div class="col col-sm-auto ms-sm-auto">
                <div class="row g-1">
                    <div class="col col-sm-auto">
                        <a href="<?= APP_BASE_URL . ($isEditing ? "/actions/index/$id" : "/routines") ?>" class="btn btn-secondary btn-responsive">Voltar</a>
                    </div>

                    <div class="col col-sm-auto">
                        <button type="submit" class="btn btn-primary btn-responsive">
                            <?= $isEditing ? 'Salvar' : 'Cadastrar' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>