<?php
Alert::render('actions/upsert');
require APP_ROOT . "/Views/routines/components/summary.php";
?>

<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <h4 class="fw-bold mb-0"><?= $isEditing ? 'Editar' : 'Cadastrar' ?> ação</h4>
    <p class="text-muted mb-2">
        <?= $isEditing
            ? 'Atualize as informações da ação selecionada'
            : 'Preencha os dados para cadastrar uma nova ação' ?>
    </p>
    <hr class="mt-0">
    <form name="actionForm" method="POST" action="<?= APP_BASE_URL ?>/actions/upsert/<?= $routine_id ?><?= $isEditing ? "/$id" : '' ?>" novalidate class="d-flex flex-column mt-1 gap-3">
        <div class="row g-3">
            <?php Input::text($data, 'name', 'Nome', 'Ex: Ação', 'col-12 col-sm-9') ?>
            
            <?php Input::select($data, 'priority', 'Prioridade', $priorities, 'col-12 col-sm-3') ?>
        </div>

        <div class="row justify-content-between g-1">
            <?php if ($isEditing): ?>
                <div class="col-4 col-sm-auto">
                    <span
                        class="btn btn-danger confirm-action btn-responsive"
                        data-href="<?= APP_BASE_URL ?>/actions/delete/<?= $routine_id ?>/<?= $id ?>"
                        data-title="Quer mesmo deletar essa ação?"
                        data-html='Essa ação é irreversível.'
                        data-confirm-text="Sim, deletar"
                        data-icon="warning">
                        Deletar
                    </span>
                </div>
            <?php endif; ?>

            <div class="<?= $isEditing ? 'col-8' : 'col-12' ?> col-sm-auto ms-sm-auto">
                <div class="row g-1">
                    <div class="col-6 col-sm-auto">
                        <a href="<?= APP_BASE_URL ?>/actions/index/<?= $routine_id ?>" class="btn btn-secondary btn-responsive">Voltar</a>
                    </div>

                    <div class="col-6 col-sm-auto">
                        <button type="submit" class="btn btn-primary btn-responsive">
                            <?= $isEditing ? 'Salvar' : 'Cadastrar' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>