<?php Alert::render('users/profile') ?>
<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <h4 class="fw-bold mb-0">Olá, <span class="text-secondary"><?= Helper::abbreviateName($name) ?></span></h4>
    <p class="text-muted mb-2">Aqui estão seus dados, altere sua senha se necessário</p>
    <hr class="mt-0">
    <form name="userForm" method="POST" action="<?= APP_BASE_URL ?>/users/profile" novalidate class="d-flex flex-column mt-1 gap-3">
        <div class="row g-3">
            <?php Display::text($name, 'name', 'Nome', 'col-12 col-sm-6') ?>

            <?php Display::text($email, 'email', 'E-mail', 'col-12 col-sm-3') ?>

            <?php Display::enum($level, 'level', 'Nível', 'col-12 col-sm-3') ?>

            <?php Input::password($data, 'password', 'Senha', '', 'col-12 col-sm-6') ?>

            <?php Input::password($data, 'password_confirm', 'Confirme a Senha', '', 'col-12 col-sm-6') ?>
        </div>

        <small class="text-center text-muted mb-0">A senha deve conter pelo menos 8 caracteres, incluindo ao menos 1 letra maiúscula, 1 letra minúscula, 1 número e 1 caractere especial.</small>

        <div class="row justify-content-end g-1">
            <div class="col-12 col-sm-auto">
                <button type="submit" class="btn btn-primary btn-responsive">Salvar</button>
            </div>
        </div>
    </form>
</div>