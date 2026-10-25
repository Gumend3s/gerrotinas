<?php

class Routines extends Controller
{
    private const MODELS = [
        'routineModel' => 'RoutineModel',
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
     * Retrieves all active routines and loads the routine management view.
     * This page allows users to access individual routine details.
     *
     * @return void
     */
    public function index(): void
    {
        $this->view('routines/index', ['routines' => Auth::canBypass() ? $this->routineModel->getActive() : $this->routineModel->getActiveByBusiness($_SESSION[APP_SESSION_KEY]['user_business_id'])]);
    }

    /**
     * Creates or updates a routine based on the provided ID.
     *
     * Handles form data loading, validation, routine creation,
     * routine updates and automatic reactivation of deleted routines
     * with the same CNPJ.
     *
     * @param int|string|null $id Routine ID for update, or null for creation.
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
     * Soft deletes a routine by ID.
     *
     * @param int|string $id Routine ID.
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
     * Retrieves the routine data, converts status values to their display labels,
     * and exports the records using the corresponding field labels.
     *
     * @return void
     */
    public function export(): void
    {
        $routines = $this->routineModel->export();
        if ($routines) {
            foreach ($routines as $routine) if (isset($routine->status)) $routine->status = Label::enum('status', $routine->status);

            $header = array_map(fn($field) => Label::field('routine', $field), []);
            $fileName = "Rotinas";

            Helper::exportCsv($routines, $header, $fileName);
        } else {
            Alert::flash('routines', 'Erro: falha ao tentar exportar as rotinas', 'error');
            Helper::redirect('routines');
        }
    }

    /**
     * Returns the data required to initialize the view.
     *
     * Includes values that are not directly related to the form fields,
     * such as available states.
     *
     * @return array View initialization data.
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
     * Returns the initial form values.
     *
     * Populates the form with existing routine data when editing,
     * or returns empty values when creating a new routine.
     *
     * @param int|string|null $id Routine ID when editing, or null for creation.
     *
     * @return array Form field values.
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
     * Processes raw form input into routine data.
     *
     * Applies input sanitization and formatting before validation
     * and persistence.
     *
     * @return array Processed routine data.
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
     * Validates routine form data.
     *
     * Adds validation errors, checks duplicated routine information,
     * handles deleted routine reactivation and resolves the location ID.
     *
     * @param array  $data Routine form data.
     *
     * @return array Routine data with validation results.
     */
    private function validateFormData(array $data): array
    {
        $data['name_error'] = empty($data['name']) ? 'Preencha o campo Nome' : (!Validate::name($data['name']) ? 'Nome possui caracteres inválidos' : null);
        $data['project_id_error'] = empty($data['project_id']) ? 'Escolha um Projeto' : null;
        $data['priority_error'] = empty($data['priority']) ? 'Escolha uma Prioridade' : null;
        $data['is_recurrent_error'] = $data['is_recurrent'] === '' ? 'Escolha Recorrente' : null;

        $data['recurrence_rule_error'] = $data['is_recurrent'] && empty($data['recurrence_rule']) ? 'Escolha o tipo de Recorrência' : null;
        $data['recurrence_value_error'] = $data['is_recurrent'] && empty($data['recurrence_value']) ? 'Escolha o Período de Recorrência' : null;

        return $data;
    }

    /**
     * Creates or updates a routine based on the provided data.
     *
     * Handles routine creation, updates and automatic reactivation
     * of previously deleted routines with the same CNPJ.
     *
     * @param array  $data Validated routine data.
     *
     * @return array Contains the routine save status and message.
     *               The first element indicates success and the second
     *               contains the corresponding feedback message.
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
