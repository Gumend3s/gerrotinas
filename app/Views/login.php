<div class="d-flex justify-content-center align-items-center min-vh-100">
    <div class="card p-0 shadow" style="max-width: 420px;">
        <div class="text-center">
            <img class="img-fluid w-75 py-3" src="<?= Helper::escape(APP_BASE_URL) . '/public/img/logo.svg' ?>">
            <h3 class="fw-bold mb-0">Bem-vindo!</h3>
            <p class="small text-muted">Informe seus dados de login abaixo</p>
        </div>
        <div class="p-3 pt-0">
            <form method="POST" action="<?= Helper::escape(APP_BASE_URL) . '/login' ?>">
                <input type="hidden" name="csrf_token" value="<?= Helper::escape($csrf_token ?? '') ?>">
                <div class="mb-2">
                    <label for="cpf" class="form-label">CPF</label>
                    <input id="cpf" name="cpf" type="text" class="form-control <?= ($error ?? '') ? 'is-invalid' : '' ?>" value="<?= Helper::escape($cpf ?? '') ?>" inputmode="numeric" autocomplete="off" />
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Senha</label>
                    <div class="position-relative">
                        <input id="password" name="password" type="password" class="input-action form-control pe-5 <?= ($error ?? '') ? 'is-invalid' : '' ?>" autocomplete="off" />
                        <i class="bi bi-eye position-absolute translate-middle-y me-3 input-action-icon cursor-pointer" id="toggleIcon" role="button" tabindex="-1" aria-label="Mostrar senha" onclick="togglePassword()"></i>
                        <div class="invalid-feedback text-center">Usuário ou senha incorretos!</div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
                <small class="text-muted text-center d-block pt-1">Acesso exclusivo para usuários cadastrados</small>
            </form>
        </div>
    </div>
</div>