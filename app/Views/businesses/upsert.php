<?php Alert::render('businesses/upsert') ?>
<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <h4 class="fw-bold mb-0"><?= $isEditing ? 'Editar' : 'Cadastrar' ?> empresa</h4>
    <p class="text-muted mb-2">
        <?= $isEditing
            ? 'Atualize as informações da empresa selecionada'
            : 'Preencha os dados para cadastrar uma nova empresa' ?>
    </p>
    <hr class="mt-0">
    <form name="businessForm" method="POST" action="<?= APP_BASE_URL ?>/businesses/upsert<?= $isEditing ? "/$id" : '' ?>" novalidate class="d-flex flex-column mt-1 gap-3">

        <div class="row g-3">
            <?php Input::text($data, 'legal_name', 'Razão Social', 'Ex: ...', 'col-12 col-sm-12') ?>

            <?php Input::text($data, 'trade_name', 'Nome Fantasia', 'Ex: ...', 'col-12 col-sm-8') ?>

            <?php Input::numeric($data, 'cnpj', 'CNPJ', 'Ex: 00.000.000/0001-00', 'col-12 col-sm-4', classes: 'cnpj') ?>
        </div>

        <div class="row justify-content-between g-1">
            <?php if ($isEditing): ?>
                <div class="col-4">
                    <span
                        class="btn btn-danger confirm-action btn-responsive"
                        data-href="<?= APP_BASE_URL ?>/businesses/delete/<?= $id ?>"
                        data-title="Quer mesmo deletar essa empresa?"
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
                        <a href="<?= APP_BASE_URL ?>/businesses/index" class="btn btn-secondary btn-responsive">Voltar</a>
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