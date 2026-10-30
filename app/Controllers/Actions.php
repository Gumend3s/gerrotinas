<?php

class Actions extends Controller
{
    private const MODELS = [
        'actionModel' => 'ActionModel',
        'routineModel' => 'RoutineModel',
    ];

    private ActionModel $actionModel;
    private RoutineModel $routineModel;

    private const REQUIRED_ACCESS = ['FINANCE' => 'ADMINISTRATOR', 'SALES' => 'ADMINISTRATOR'];

    public function __construct()
    {
        if (!Auth::hasAccess(self::REQUIRED_ACCESS)) {
            Alert::flash('index', '<strong>Acesso bloqueado</strong> <br>Método de acesso inválido ou falta de permissão', 'error');
            Helper::redirect('./');
        }

        foreach (self::MODELS as $property => $model) $this->$property = $this->model($model);
    }

    /**
     * Returns an active routine by ID.
     *
     * If the routine cannot be found, the user is redirected to the
     * routines page with an error message.
     *
     * @param int|string $routine_id Routine ID.
     *
     * @return object Routine data.
     */
    private function getRoutine(int|string $routine_id): object
    {
        if (!($routine = $this->routineModel->findBy(['id' => $routine_id, 'status' => 'ACTIVE']))) {
            Alert::flash('routines', 'Erro: rotina não encontrada', 'error');
            Helper::redirect('routines');
        }

        if (!Auth::canBypass() && $routine->business_id !== $_SESSION[APP_SESSION_KEY]['user_business_id']) {
            Alert::flash('routines', 'Erro: você não pode acessar essa rotina', 'error');
            Helper::redirect('routines');
        }

        return $routine;
    }

    /**
     * Returns routine data formatted for use in the actions views.
     *
     * @param object $routine Routine data.
     *
     * @return array Formatted routine data.
     */
    private function getRoutineData(object $routine): array
    {
        return [
            'routine_id' => $routine->id,
            'routine_name' => $routine->name,
            'project_name' => $routine->project_name,
            'routine_priority' => Label::enum('priority', $routine->priority),
        ];
    }

    /**
     * Returns an active action matching the specified routine and ID.
     *
     * If the action cannot be found, the user is redirected to the
     * actions page with an error message.
     *
     * @param int|string $routine_id Routine ID.
     * @param int|string $id          Action ID.
     *
     * @return object Action data.
     */
    private function getAction(int|string $routine_id, int|string $id): object
    {
        if (!($action = $this->actionModel->findBy(['routine_id' => $routine_id, 'id' => $id, 'status' => 'ACTIVE']))) {
            Alert::flash('actions', 'Erro: ação não encontrada', 'error');
            Helper::redirect("actions/index/$routine_id");
        }

        return $action;
    }

    /**
     * Displays the action list for a routine.
     *
     * Retrieves the routine data and all active actions associated with
     * the specified routine, then loads the action management view.
     *
     * @param int|string $routine_id The ID of the routine.
     *
     * @return void
     */
    public function index(int|string $routine_id): void
    {
        $routine = $this->getRoutine($routine_id);

        $data = array_merge($this->getRoutineData($routine), [
            'actions' => $this->actionModel->getRoutineActiveActions($routine_id),
        ]);

        $this->view('actions/index', $data);
    }

    /**
     * Creates or updates an action for the specified routine.
     *
     * Handles routine and action retrieval, form data loading and validation,
     * action creation, action updates and automatic reactivation of deleted
     * actions with the same name for the routine.
     *
     * @param int|string      $routine_id Routine ID.
     * @param int|string|null $id          Action ID for update, or null for creation.
     *
     * @return void
     */
    public function upsert(int|string $routine_id, int|string|null $id = null): void
    {
        $routine = $this->getRoutine($routine_id);

        $data = [
            'id' => $id,
            'routine_id' => $routine_id,
            'isEditing' => $id !== null,
        ];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $data = array_merge($data, $this->getInitialData($routine_id, $id));
        else {
            $data = array_merge($data, $this->getFormData());

            $data = $this->validateFormData($data);
            if (!array_filter(Helper::filterKeysContaining($data, '_error'))) {
                [$success, $message] = $this->save($data);

                if ($success) Alert::flash('actions', $message);
                else Alert::flash('actions', 'Erro: falha ao ' . ($data['isEditing'] ? 'editar' : 'cadastrar') . ' ação', 'error');

                Helper::redirect("actions/index/{$data['routine_id']}");
            }
        }

        $this->view('actions/upsert', array_merge($data, $this->getRoutineData($routine), $this->getViewData()));
    }

    /**
     * Soft deletes an action by ID for the specified routine.
     *
     * @param int|string $routine_id  Routine ID.
     * @param int|string $id Action ID.
     *
     * @return void
     */
    public function delete(int|string $routine_id, int|string $id): void
    {
        $this->getAction($routine_id, $id);

        if ($this->actionModel->softDelete($id)) {
            Alert::flash('actions', "Operação deletada com sucesso");
            Helper::redirect("actions/index/$routine_id");
        } else {
            Alert::flash('actions/upsert', 'Erro: falha ao deletar ação', 'error');
            Helper::redirect("actions/upsert/$routine_id/$id");
        }
    }

    /**
     * Exports active actions from a routine to a CSV file.
     *
     * Retrieves the actions associated with the specified routine, converts
     * status values to their display labels, and exports the records using the
     * corresponding field labels.
     *
     * @param int|string $routine_id Routine ID.
     *
     * @return void
     */
    public function export(int|string $routine_id): void
    {
        $routine = $this->getRoutine($routine_id);

        $actions = $this->actionModel->exportRoutineActions($routine_id);
        if ($actions) {
            foreach ($actions as $action) if (isset($action->status)) {
                $action->priority = Label::enum('priority', $action->priority);
                $action->status = Label::enum('status', $action->status);
            }

            $header = array_map(fn($field) => Label::field('action', $field), ['id', 'name', 'creator_name', 'priority', 'created_at', 'updated_at', 'status']);
            $fileName = "Operações $routine->customer_name [$routine->name]";

            Helper::exportCsv($actions, $header, $fileName);
        } else {
            Alert::flash('actions', 'Erro: falha ao tentar exportar as operações da rotina', 'error');
            Helper::redirect("actions/index/$routine_id");
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
        $priorities = Helper::extractEnumValues($this->actionModel->getPriorities());

        return [
            'priorities' => array_combine($priorities, array_map(fn($priority) => Label::enum('priority', $priority), $priorities)),
        ];
    }

    /**
     * Returns the initial form values.
     *
     * Populates the form with existing action data when editing,
     * or returns empty values when creating a new action.
     *
     * @param int|string      $routine_id Routine ID.
     * @param int|string|null $id          Action ID when editing, or null for creation.
     *
     * @return array Form field values.
     */
    private function getInitialData(int|string $routine_id, int|string|null $id): array
    {
        $action = $id !== null ? $this->getAction($routine_id, $id) : null;

        return [
            'name' => $action->name ?? '',
            'priority' => $action->priority ?? '',
        ];
    }

    /**
     * Processes raw form input into action data.
     *
     * Applies input sanitization and formatting before validation
     * and persistence.
     *
     * @return array Processed action data.
     */
    private function getFormData(): array
    {
        $formInput = filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW) ?: [];
        return [
            'shouldReactivate' => false,
            'name' => Helper::standardizeName($formInput['name'] ?? ''),
            'priority' => trim($formInput['priority'] ?? ''),
        ];
    }

    /**
     * Validates action form data.
     *
     * Adds validation errors and checks for duplicated action
     * information within the related routine.
     *
     * @param array  $data Action form data.
     *
     * @return array Action data with validation results.
     */
    private function validateFormData(array $data): array
    {
        $data['name_error'] = empty($data['name']) ? 'Preencha o campo Nome' : (!Validate::name($data['name']) ? 'Nome possui caracteres inválidos' : null);
        $data['priority_error'] = empty($data['priority']) ? 'Escolha uma Prioridade' : null;

        if ($data['name_error'] === null && ($existingAction = $this->actionModel->findBy(['routine_id' => $data['routine_id'], 'name' => $data['name']])) && $existingAction->id != $data['id']) {
            if ($existingAction->status === 'DELETED' && !$data['isEditing']) {
                $data['shouldReactivate'] = true;
                $data['existingAction'] = $existingAction;
            } else $data['name_error'] = 'Uma ação dessa rotina já possui esse nome';
        }

        return $data;
    }

    /**
     * Creates or updates an action based on the provided data.
     *
     * Handles action creation, updates and automatic reactivation
     * of previously deleted actions with the same name.
     *
     * @param array  $data Validated action data.
     *
     * @return array Contains the action save status and message.
     *               The first element indicates success and the second
     *               contains the corresponding feedback message.
     */
    private function save(array $data): array
    {
        if ($data['isEditing']) {
            $this->getAction($data['routine_id'], $data['id']);

            $success = $this->actionModel->update($data['id'], $data);

            $message = 'Editado com sucesso';
        } else {
            if ($data['shouldReactivate']) {
                $data['status'] = 'ACTIVE';

                $success = $this->actionModel->update($data['existingAction']->id, $data);
            } else $success = $this->actionModel->create($data);

            $message = 'Cadastrado com sucesso';
        }

        return [$success, $message];
    }
}
