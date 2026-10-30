<?php Alert::render('projects') ?>
<div class="d-flex flex-column card shadow-sm border-0 p-3 p-md-4">
    <h4 class="fw-bold mb-0">Projetos</h4>
    <p class="text-muted mb-2">
        <?= $isEditing
            ? 'Atualize as informações do projeto selecionado'
            : 'Preencha os dados para cadastrar um novo projeto' ?>
    </p>
    <hr class="mt-0">
    <form name="projectForm" method="POST" action="<?= APP_BASE_URL ?>/projects/upsert<?= $isEditing ? "/$id" : '' ?><?= $routine_id != '' ? "?routine_id=$routine_id" : '' ?>" novalidate class="row g-1">
        <?php Input::text($data, 'name', 'Nome', 'Ex: ...', 'col-12 col-sm') ?>
        <div class="col col-sm-auto">
            <label class="invisible d-none d-sm-block">.</label>
            <div class="row g-1">
                <?php if ($isEditing): ?>
                    <div class="col col-sm-auto">
                        <a href="<?= APP_BASE_URL ?>/projects/index<?= $routine_id != '' ? "?routine_id=$routine_id" : '' ?>" class="btn btn-secondary btn-responsive">Cancelar</a>
                    </div>
                <?php endif; ?>
                <div class="col col-sm-auto">
                    <button type="submit" class="btn btn-primary btn-responsive">
                        <?= $isEditing ? 'Salvar' : 'Cadastrar' ?>
                    </button>
                </div>
                <div class="<?= $isEditing ? 'col-4' : 'col-6' ?> col-sm-auto">
                    <a href="<?= APP_BASE_URL ?>/routines/upsert<?= $routine_id != '' ? "/$routine_id" : '' ?>" class="btn btn-secondary btn-responsive">Voltar</a>
                </div>
            </div>
        </div>
    </form>
    <p class="text-muted mb-2 mt-4">Gerencie os projetos do sistema</p>
    <hr class="mt-0">
    <div class="d-flex flex-column flex-sm-row gap-1">
        <input id="search" data-table="#projects" class="form-control fs-7" type="text" placeholder="Pesquisar...">
    </div>
    <div class="table-responsive mt-3">
        <table id="projects" class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Criador</th>
                    <?php if (Auth::canBypass()): ?>
                        <th>Empresa</th>
                    <?php endif; ?>
                    <th class="fit-column"></th>
                </tr>
            </thead>
            <tbody class="align-middle">
                <?php if (!$projects): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">
                            Nenhum projeto encontrado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td>
                                <?= Helper::escape($project->name) ?>
                            </td>
                            <td>
                                <?= Helper::abbreviateName($project->creator_name) ?>
                            </td>
                            <td>
                                <?php if (Auth::canBypass()): ?>
                                    <?= Helper::abbreviateName($project->business_name) ?>
                                <?php endif; ?>
                            </td>
                            <td class="fit-column">
                                <span class="flex-shrink-0">
                                    <a href="<?= APP_BASE_URL ?>/projects/upsert/<?= $project->id ?><?= $routine_id != '' ? "?routine_id=$routine_id" : '' ?>" class="btn btn-primary btn-sm" aria-label="Editar categoria"><i class="bi bi-pencil-square"></i></a>
                                    <span
                                        class="btn btn-sm btn-danger confirm-action"
                                        data-href="<?= APP_BASE_URL ?>/projects/delete/<?= $project->id ?><?= $routine_id != '' ? "?routine_id=$routine_id" : '' ?>"
                                        data-title="Quer mesmo deletar esse projeto?"
                                        data-html='Essa ação é irreversível.'
                                        data-confirm-text="Sim, deletar"
                                        data-icon="warning">
                                        <i class="bi bi-trash"></i>
                                    </span>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>