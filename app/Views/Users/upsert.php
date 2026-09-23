<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <h4 class="fw-bold mb-0"><?= $isEditing ? 'Editar' : 'Cadastrar' ?> usuário</h4>
    <p class="text-muted mb-2">
        <?= $isEditing
            ? 'Atualize as informações do usuário selecionado'
            : 'Preencha os dados para cadastrar um novo usuário' ?>
    </p>
    <hr class="mt-0">
    <form name="userForm" method="POST" action="<?= APP_BASE_URL ?>/users/upsert<?= $isEditing ? "/{$id}" : '' ?>" novalidate class="d-flex flex-column mt-1 gap-3">

        <div class="row g-3">
            <?php Input::text($data, 'name', 'Nome', 'Ex: João da Silva', 'col-12 col-sm-8') ?>

            <?php Input::numeric($data, 'cpf', 'CPF', 'Ex: 000.000.000-00', 'col-12 col-sm-4') ?>

            <?php if ($canChangeDepartment): ?>
                <?= Input::select(
                    $data,
                    'department',
                    'Departamento',
                    $departments,
                    'col-12 col-sm-6'
                ) ?>
            <?php else: ?>
                <?php Display::text($data, 'department', 'Departamento', 'col-12 col-sm-6') ?>
            <?php endif; ?>

            <?= Input::select(
                $data,
                'level',
                'Nível',
                $levels,
                'col-12 col-sm-6'
            ) ?>
        </div>
        <div class="d-flex justify-content-between">
            <?php if (!$isOwnUser && $isEditing): ?>
                <div>
                    <span class="btn btn-danger confirm-action" data-href="<?= APP_BASE_URL ?>/users/delete/<?= $id ?>" data-title="Quer mesmo deletar esse usuário?" data-text="Essa ação é irreversível" data-confirm-text="Sim, deletar">Deletar</span>
                    <span class="btn btn-secondary confirm-action" data-href="<?= APP_BASE_URL ?>/users/resetPassword/<?= $id ?>" data-title="Quer mesmo redefinir a senha desse usuário?" data-text="Essa ação é irreversível" data-confirm-text="Sim, redefinir">Redefinir Senha</span>
                </div>
            <?php endif; ?>

            <div class="ms-auto">
                <a href="<?= APP_BASE_URL ?>/users" class="btn btn-secondary">Voltar</a>
                <button type="submit" class="btn btn-primary">
                    <?= $isEditing ? 'Salvar' : 'Cadastrar' ?>
                </button>
            </div>
        </div>
    </form>
</div>