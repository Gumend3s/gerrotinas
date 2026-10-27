<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4 mb-2">
    <h4 class="fw-bold mb-0">Rotina</h4>
    <p class="text-muted mb-2">Informações da rotina</p>
    <hr class="mt-0">
    <div class="d-flex flex-column mt-1 gap-3">
        <div class="row g-3">
            <?php Display::text($data, 'routine_id', 'Código', 'col-12 col-sm-2') ?>
            
            <?php Display::text($data, 'routine_name', 'Nome', 'col-12 col-sm-4') ?>

            <?php Display::text($data, 'project_name', 'Projeto', 'col-12 col-sm-4') ?>

            <?php Display::text($data, 'routine_priority', 'Prioridade', 'col-12 col-sm-2') ?>
        </div>

        <?php if (isset($showActions)): ?>
            <div class="d-flex gap-1 justify-content-end">
                <?php if (in_array('go_back', $showActions, true)): ?>
                    <div class="col-12 col-sm-auto">
                        <a href="<?= APP_BASE_URL ?>/routines" class="btn btn-secondary btn-responsive">Voltar</a>
                    </div>
                <?php endif; ?>
                <?php if (in_array('go_to_upsert', $showActions, true)): ?>
                    <div class="col-12 col-sm-auto">
                        <a href="<?= APP_BASE_URL ?>/routines/upsert/<?= $routine_id ?>" class="btn btn-primary btn-responsive">Editar</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>