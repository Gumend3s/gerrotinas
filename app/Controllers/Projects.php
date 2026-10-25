<?php

class Projects extends Controller
{
    private const MODELS = [
        'projectModel' => 'ProjectModel',
    ];

    private const REQUIRED_ACCESS = ['ADMINISTRATOR', 'MASTER'];

    public function __construct()
    {
        if (!Auth::hasAccess(self::REQUIRED_ACCESS)) {
            Alert::flash('index', '<strong>Acesso bloqueado</strong> <br>Método de acesso inválido ou falta de permissão', 'error');
            Helper::redirect('./');
        }

        foreach (self::MODELS as $property => $model) $this->$property = $this->model($model);
    }

    /**
     * Retrieves an active project by ID.
     *
     * Redirects to the projects listing if the project does not exist
     * or is not active.
     *
     * @param int|string $id Project ID.
     *
     * @return object Project data.
     */
    private function getProject(int|string $id): object
    {
        if (!($project = $this->projectModel->findBy(['id' => $id], activeOnly: true))) {
            Alert::flash('projects', 'Erro: projeto não encontrado', 'error');
            Helper::redirect('projects' . (isset($_GET['routine_id']) ? "?routine_id={$_GET['routine_id']}" : ''));
        }

        return $project;
    }

    /**
     * Displays the project management page.
     *
     * Loads the view with the data required to list and manage projects.
     * This page is not related to a specific project.
     *
     * @return void
     */
    public function index(): void
    {
        $this->view('projects/index', array_merge($this->getViewData(), ['isEditing' => false]));
    }

    /**
     * Creates or updates a project based on the provided ID.
     *
     * Handles project validation, project creation,
     * updates and automatic reactivation of deleted projects
     * with the same name.
     *
     * @param int|string|null $id Project ID for update, or null for creation.
     *
     * @return void
     */
    public function upsert(int|string|null $id = null): void
    {
        $data = [
            'id' => $id,
            'isEditing' => $id !== null,
        ];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $data = array_merge($data, $this->getInitialData($id));
        else {
            $data = array_merge($data, $this->getFormData());

            $data = $this->validateFormData($data);

            if (!array_filter(Helper::filterKeysContaining($data, '_error'))) {
                [$success, $message] = $this->save($data);

                if ($success) Alert::flash('projects', $message);
                else Alert::flash('projects', 'Erro: falha ao ' . ($data['isEditing'] ? 'editar' : 'cadastrar') . ' projeto', 'error');

                Helper::redirect('projects' . (isset($_GET['routine_id']) ? "?routine_id={$_GET['routine_id']}" : ''));
            }
        }

        $this->view('projects/index', array_merge($data, $this->getViewData()));
    }

    /**
     * Soft deletes a project.
     *
     * @param int|string $id Project ID.
     *
     * @return void
     */
    public function delete(int|string $id): void
    {
        if ($this->projectModel->softDelete($id)) Alert::flash('projects', "Grupo de clientes deletado com sucesso");
        else Alert::flash('projects', 'Erro: falha ao deletar projeto', 'error');

        Helper::redirect('projects' . (isset($_GET['routine_id']) ? "?routine_id={$_GET['routine_id']}" : ''));
    }

    /**
     * Returns the data required to initialize the view.
     *
     * Includes the active projects available for the listing.
     *
     * @return array View initialization data.
     */
    private function getViewData(): array
    {
        return [
            'projects' => Auth::canBypass() ? $this->projectModel->getActive() : $this->projectModel->getActiveByBusiness($_SESSION[APP_SESSION_KEY]['user_business_id']),
            'routine_id' => $_GET['routine_id'] ?? '',
        ];
    }

    /**
     * Returns the initial form values.
     *
     * Loads the existing project when editing,
     * or returns empty values when creating a new project.
     *
     * @param int|string|null $id Project ID for editing, or null for creation.
     *
     * @return array Form field values.
     */
    private function getInitialData(int|string|null $id): array
    {
        $project = $id !== null ? $this->getProject($id) : null;

        return [
            'name' => $project->name ?? '',
        ];
    }

    /**
     * Processes raw form input into project data.
     *
     * Applies input sanitization and formatting before validation
     * and persistence.
     *
     * @return array Processed project data.
     */
    private function getFormData(): array
    {
        $formInput = filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW) ?: [];

        return [
            'shouldReactivate' => false,
            'name' => Helper::standardizeName($formInput['name'] ?? ''),
        ];
    }

    /**
     * Validates project form data.
     *
     * Adds validation errors, checks for duplicate project names,
     * and handles automatic reactivation of deleted projects with
     * the same name.
     *
     * @param array  $data Project form data.
     *
     * @return array Project data with validation results.
     */
    private function validateFormData(array $data): array
    {
        $data['name_error'] = empty($data['name']) ? "Preencha o campo Nome" : (!Validate::name($data['name']) ? 'O nome possui caracteres inválidos' : null);

        if ($data['name_error'] === null && ($existingProject = $this->projectModel->findBy(['name' => $data['name']])) && $existingProject->id != $data['id']) {
            if ($existingProject->status === 'DELETED' && !$data['isEditing']) {
                $data['shouldReactivate'] = true;
                $data['existingProject'] = $existingProject;
            } else $data['name_error'] = 'Já existe um projeto com esse nome';
        }

        return $data;
    }

    /**
     * Creates or updates a project based on the provided data.
     *
     * Handles project creation, updates and automatic reactivation
     * of previously deleted projects with the same name.
     *
     * @param array  $data Validated project data.
     *
     * @return array Contains the project save status and message.
     *               The first element indicates success and the second
     *               contains the corresponding feedback message.
     */
    private function save(array $data): array
    {
        if ($data['isEditing']) {
            $this->getProject($data['id']);

            $success = $this->projectModel->update($data['id'], $data);

            $message = 'Editado com sucesso';
        } else {
            if ($data['shouldReactivate']) {
                $data['status'] = 'ACTIVE';

                $success = $this->projectModel->update($data['existingProject']->id, $data);
            } else $success = $this->projectModel->create($data);

            $message = 'Cadastrado com sucesso';
        }

        return [$success, $message];
    }
}
