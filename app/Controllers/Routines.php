<?php

class Routines extends Controller
{
    private const MODELS = [
        'routineModel' => 'RoutineModel',
        'projectModel' => 'ProjectModel',
    ];

    private RoutineModel $routineModel;
    private ProjectModel $projectModel;

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
     * Retrieves an active routine by its ID.
     *
     * If the routine is not found or is not active, displays an error message
     * and redirects the user to the routine list.
     *
     * @param int|string $id ID of the routine to retrieve.
     *
     * @return object The active routine data object.
     */
    private function getRoutine(int|string $id): object
    {
        if (!($routine = $this->routineModel->findBy(['id' => $id], activeOnly: true))) {
            Alert::flash('routines', 'Erro: rotina não encontrada', 'error');
            Helper::redirect('routines');
        }

        return $routine;
    }

    /**
     * Displays the main routine list.
     *
     * Retrieves active routines according to the current user's access level.
     * Users who can bypass business restrictions can view all active routines;
     * other users can view only routines associated with their business.
     *
     * @return void
     */
    public function index(): void
    {
        $this->view('routines/index', ['routines' => Auth::canBypass() ? $this->routineModel->getActive() : $this->routineModel->getActiveByBusiness($_SESSION[APP_SESSION_KEY]['user_business_id'])]);
    }

    /**
     * Creates a new routine or updates an existing one.
     *
     * Loads initial form values for GET requests. For POST requests, retrieves
     * and validates submitted data, saves the routine if validation succeeds,
     * displays a feedback message, and redirects after processing.
     *
     * If validation fails, redisplays the form with the submitted data and
     * validation errors.
     *
     * @param int|string|null $id ID of the routine to update, or null to create a new routine.
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

                if ($success) Alert::flash('routines', $message);
                else Alert::flash('routines', 'Erro: falha ao ' . ($data['isEditing'] ? 'editar' : 'cadastrar') . ' rotina', 'error');

                Helper::redirect('routines');
            }
        }

        $this->view('routines/upsert', array_merge($data, $this->getViewData()));
    }


    /**
     * Soft-deletes an active routine by its ID.
     *
     * Verifies that the routine exists and is active before attempting deletion.
     * Displays a success or error message and redirects accordingly.
     *
     * @param int|string $id ID of the routine to soft-delete.
     *
     * @return void
     */
    public function delete(int|string $id): void
    {
        $this->getRoutine($id);

        if ($this->routineModel->softDelete($id)) {
            Alert::flash('routines', "Rotina deletada com sucesso");
            Helper::redirect('routines');
        } else {
            Alert::flash('routines/upsert', 'Erro: falha ao deletar rotina', 'error');
            Helper::redirect("routines/upsert/$id");
        }
    }

    /**
     * Exports active routines to a CSV file.
     *
     * Retrieves routines according to the current user's access level,
     * converts status, recurrence, and recurrence flag values into their
     * display labels, and exports the records with localized column headers.
     *
     * If no routines are returned, displays an error message and redirects
     * to the routine list.
     *
     * @return void
     */
    public function export(): void
    {
        $routines = Auth::canBypass()
            ? $this->routineModel->export()
            : $this->routineModel->exportByBusiness($_SESSION[APP_SESSION_KEY]['user_business_id']);

        if ($routines) {
            foreach ($routines as $routine) if (isset($routine->status)) {
                $routine->status = Label::enum('status', $routine->status);
                $routine->is_recurrent = Label::enum('yes/no', $routine->is_recurrent);
                $routine->recurrence_rule = Label::enum('recurrence_rule', $routine->recurrence_rule ?? '');
            }

            $header = array_map(fn($field) => Label::field('routine', $field), Auth::canBypass() ? ['id', 'name', 'is_recurrent', 'recurrence_rule', 'recurrence_value', 'priority', 'project_name', 'creator_name', 'business_name', 'created_at', 'updated_at', 'status',] : ['id', 'name', 'is_recurrent', 'recurrence_rule', 'recurrence_value', 'priority', 'project_name', 'creator_name', 'created_at', 'updated_at', 'status',]);
            $fileName = "Rotinas";

            Helper::exportCsv($routines, $header, $fileName);
        } else {
            Alert::flash('routines', 'Erro: falha ao tentar exportar as rotinas', 'error');
            Helper::redirect('routines');
        }
    }

    /**
     * Retrieves the data required to populate the routine form view.
     *
     * Provides the available projects, priority options, and recurrence rule
     * options, using display labels for the latter two. Project options are
     * restricted to the current user's business unless access restrictions
     * can be bypassed.
     *
     * @return array Associative array containing the view data.
     */
    private function getViewData(): array
    {
        $priorities = Helper::extractEnumValues($this->routineModel->getPriorities());
        $recurrence_rules = Helper::extractEnumValues($this->routineModel->getRecurrenceRules());

        return [
            'projects' => array_column(Auth::canBypass() ? $this->projectModel->getActive() : $this->projectModel->getActiveByBusiness($_SESSION[APP_SESSION_KEY]['user_business_id']), 'name', 'id'),
            'priorities' => array_combine($priorities, array_map(fn($priority) => Label::enum('priority', $priority), $priorities)),
            'recurrence_rules' => array_combine($recurrence_rules, array_map(fn($recurrence_rule) => Label::enum('recurrence_rule', $recurrence_rule), $recurrence_rules)),
        ];
    }

    /**
     * Retrieves the initial values for the routine form.
     *
     * When editing, populates the form with the existing active routine's data.
     * When creating a new routine, returns empty values for the form fields.
     *
     * @param int|string|null $id ID of the routine to retrieve, or null for creation.
     *
     * @return array Associative array containing the initial form field values.
     */
    private function getInitialData(int|string|null $id): array
    {
        $routine = $id !== null ? $this->getRoutine($id) : null;

        return [
            'name' => $routine->name ?? '',
            'project_id' => $routine->project_id ?? '',
            'priority' => $routine->priority ?? '',
            'is_recurrent' => $routine->is_recurrent ?? '',
            'recurrence_rule' => $routine->recurrence_rule ?? '',
            'recurrence_value' => $routine->recurrence_value ?? '',
        ];
    }

    /**
     * Retrieves and normalizes routine form input.
     *
     * Reads the submitted POST data, standardizes the routine name, trims
     * the remaining form values, and initializes the flag used to indicate
     * whether a deleted routine should be reactivated.
     *
     * @return array Associative array containing the processed form data.
     */
    private function getFormData(): array
    {
        $formInput = filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW) ?: [];

        return [
            'shouldReactivate' => false,
            'name' => Helper::standardizeName($formInput['name'] ?? ''),
            'project_id' => trim($formInput['project_id'] ?? ''),
            'priority' => trim($formInput['priority'] ?? ''),
            'is_recurrent' => trim($formInput['is_recurrent'] ?? ''),
            'recurrence_rule' => trim($formInput['recurrence_rule'] ?? ''),
            'recurrence_value' => trim($formInput['recurrence_value'] ?? ''),
        ];
    }

    /**
     * Validates the submitted routine form data.
     *
     * Checks the routine name, project, priority, recurrence flag, and
     * recurrence settings when recurrence is enabled. Adds corresponding
     * validation error messages to the data array.
     *
     * @param array $data Routine form data to validate.
     *
     * @return array Routine form data with validation error fields.
     */
    private function validateFormData(array $data): array
    {
        $data['name_error'] = empty($data['name']) ? 'Preencha o campo Nome' : (!Validate::name($data['name']) ? 'Nome possui caracteres inválidos' : null);
        $data['project_id_error'] = empty($data['project_id']) ? 'Escolha um Projeto' : null;
        $data['priority_error'] = empty($data['priority']) ? 'Escolha uma Prioridade' : null;
        $data['is_recurrent_error'] = $data['is_recurrent'] === '' ? 'Escolha Recorrente' : null;

        $data['recurrence_rule_error'] = $data['is_recurrent'] && empty($data['recurrence_rule']) ? 'Escolha o tipo de Recorrência' : null;
        $data['recurrence_value_error'] = $data['is_recurrent'] && empty($data['recurrence_value']) ? 'Escolha o Período de Recorrência' : null;

        if ($data['project_id_error'] === null && $data['name_error'] === null && ($existingRoutine = $this->routineModel->findBy(['project_id' => $data['project_id'], 'name' => $data['name']])) && $existingRoutine->id != $data['id']) {
            if ($existingRoutine->status === 'DELETED' && !$data['isEditing']) {
                $data['shouldReactivate'] = true;
                $data['existingRoutine'] = $existingRoutine;
            } else $data['name_error'] = 'Uma rotina desse projeto já possui esse nome';
        }

        return $data;
    }

    /**
     * Creates or updates a routine using the provided form data.
     *
     * Clears recurrence settings when recurrence is disabled. For existing
     * routines, updates the specified routine after verifying that it is active.
     * For new routines, either reactivates a previously deleted routine or
     * creates a new one, depending on the supplied data.
     *
     * Returns a success flag and a corresponding feedback message.
     *
     * @param array $data Validated routine data and operation metadata.
     *
     * @return array A two-element array containing the operation result and
     *               feedback message, in the format [bool, string].
     */
    private function save(array $data): array
    {
        if (!$data['is_recurrent']) {
            $data['recurrence_rule'] = null;
            $data['recurrence_value'] = null;
        }

        if ($data['isEditing']) {
            $this->getRoutine($data['id']);

            $success = $this->routineModel->update($data['id'], $data);

            $message = 'Editado com sucesso';
        } else {
            if ($data['shouldReactivate']) {
                $data['status'] = 'ACTIVE';

                $success = $this->routineModel->update($data['existingRoutine']->id, $data);
            } else $success = $this->routineModel->create($data);

            $message = 'Cadastrado com sucesso';
        }

        return [$success, $message];
    }
}
