<?php Alert::render('users/upsert') ?>
<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <h4 class="fw-bold mb-0"><?= $isEditing ? 'Editar' : 'Cadastrar' ?> usuário</h4>
    <p class="text-muted mb-2">
        <?= $isEditing
            ? 'Atualize as informações do usuário selecionado'
            : 'Preencha os dados para cadastrar um novo usuário' ?>
    </p>
    <hr class="mt-0">
    <form name="userForm" method="POST" action="<?= APP_BASE_URL ?>/users/upsert<?= $isEditing ? "/$id" : '' ?>" novalidate class="d-flex flex-column mt-1 gap-3">

        <div class="row g-3">
            <?php Input::text($data, 'name', 'Nome', 'Ex: João da Silva', 'col-12 col-sm-8') ?>

            <?php Input::numeric($data, 'email', 'E-mail', 'Ex: email@gmail.com', 'col-12 col-sm-4') ?>

            <?php if ($canChangeBusiness): ?>
                <?= Input::select(
                    $data,
                    'business_id',
                    'Empresa',
                    $businesses,
                    'col-12 col-sm-6'
                ) ?>
            <?php else: ?>
                <?php Display::text($business_name ?? null, 'business_name', 'Empresa', 'col-12 col-sm-6') ?>
            <?php endif; ?>

            <?= Input::select(
                $data,
                'level',
                'Nível',
                $levels,
                'col-12 col-sm-6'
            ) ?>
        </div>

        <div class="row g-1">
            <?php if (!$isOwnUser && $isEditing): ?>
                <div class="col col-sm-auto">
                    <div class="row g-1">
                        <div class="col col-sm-auto">
                            <span class="btn btn-danger confirm-action btn-responsive" data-href="<?= APP_BASE_URL ?>/users/delete/<?= $id ?>" data-title="Quer mesmo deletar esse usuário?" data-text="Essa ação é irreversível" data-confirm-text="Sim, deletar" data-icon="warning">Deletar</span>
                        </div>
                        <div class="col col-sm-auto">
                            <span class="btn btn-secondary confirm-action btn-responsive" data-href="<?= APP_BASE_URL ?>/users/resetPassword/<?= $id ?>" data-title="Quer mesmo redefinir a senha desse usuário?" data-text="Essa ação é irreversível" data-confirm-text="Sim, redefinir" data-icon="warning">Redefinir Senha</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col col-sm-auto ms-sm-auto">
                <div class="row g-1">
                    <div class="col col-sm-auto">
                        <a href="<?= APP_BASE_URL ?>/users" class="btn btn-secondary btn-responsive">Voltar</a>
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